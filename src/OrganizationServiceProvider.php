<?php

namespace DagaSmart\Organization;

use DagaSmart\BizAdmin\Extend\ServiceProvider;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\TextControl;
use DagaSmart\Organization\Jobs\StudentImportJob;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

class OrganizationServiceProvider extends ServiceProvider
{
    protected $menu;

    protected function menu(): array
    {
        $menu = [];
        $menu[] = [
            'parent' => null,
            'title' => '基础维护',
            'url' => '/extension/enterprise',
            'url_type' => 1,
            'icon' => 'carbon:calendar-settings',
        ];
        $menu[] = [
            'parent' => '基础维护',
            'title' => '机构管理',
            'url' => '/extension/enterprise/index',
            'url_type' => 1,
            'icon' => 'teenyicons:school-outline',
        ];
        if (is_school_module()) {
            $menu[] = [
                'parent' => '基础维护',
                'title' => '老师管理',
                'url' => '/extension/enterprise/worker',
                'url_type' => 1,
                'icon' => 'la:chalkboard-teacher',
            ];
            $menu[] = [
                'parent' => '基础维护',
                'title' => '学生管理',
                'url' => '/extension/enterprise/student',
                'url_type' => 1,
                'icon' => 'ph:student-light',
            ];
            $menu[] = [
                'parent' => '基础维护',
                'title' => '家长管理',
                'url' => '/extension/enterprise/patriarch',
                'url_type' => 1,
                'icon' => 'ri:parent-line',
            ];
        } else {
            $menu[] = [
                'parent' => '基础维护',
                'title' => '员工管理',
                'url' => '/extension/enterprise/worker',
                'url_type' => 1,
                'icon' => 'healthicons:city-worker-outline',
            ];
        }
        $menu[] = [
            'parent' => '基础维护',
            'title' => '基础设施',
            'url' => '/extension/enterprise/facility',
            'url_type' => 1,
            'icon' => 'heroicons:building-office-2',
        ];
        $menu[] = [
            'parent' => '基础维护',
            'title' => '设备管理',
            'url' => '/extension/enterprise/device',
            'url_type' => 1,
            'icon' => 'ph:devices-light',
        ];

        return $this->menu = $menu;
    }

    protected $auth = [
        // 机构管理
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'search', 'custom_order' => 5],
        ['name' => '部门', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'department', 'custom_order' => 6],
        ['name' => '职务', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'job', 'custom_order' => 7],
        ['name' => '授权', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.index', 'abbr' => 'auth', 'custom_order' => 8],
        // 员工管理
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'search', 'custom_order' => 5],
        ['name' => '一键导入', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'import', 'custom_order' => 6],
        ['name' => '导出', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.worker', 'abbr' => 'export', 'custom_order' => 7],
        // 学生管理
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'search', 'custom_order' => 5],
        ['name' => '班级管理', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'classes', 'custom_order' => 6],
        ['name' => '一键导入', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'import', 'custom_order' => 7],
        ['name' => '导出', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.student', 'abbr' => 'export', 'custom_order' => 8],
        // 家长管理
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.patriarch', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.patriarch', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.patriarch', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.patriarch', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '导出', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.patriarch', 'abbr' => 'export', 'custom_order' => 5],
        // 基础设施
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.facility', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.facility', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.facility', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.facility', 'abbr' => 'showed', 'custom_order' => 4],
        // 设备管理
        ['name' => '新增', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.device', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.device', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.device', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.organization',  'code' => 'extension.enterprise.device', 'abbr' => 'showed', 'custom_order' => 4],
    ];

    public function boot(): void
    {
        Queue::route(StudentImportJob::class, queue: 'student-import', connection: 'redis');
    }

    /**
     * @throws Exception
     */
    public function register(): void
    {
        parent::register();

        /**加载路由**/
        parent::registerRoutes(__DIR__.'/Http/routes.php');
        /**加载语言包**/
        if ($lang = parent::getLangPath()) {
            $this->loadTranslationsFrom($lang, $this->getCode());
        }

        // 复制图标
        $source = admin_extension_path('organization/database/icon');
        $target = public_extensions_path('organization/icon');
        if (is_dir($source) && ! is_dir($target)) {
            File::copyDirectory($source, $target); // 复制整个目录及其内容到目标目录
        }
        // 复制模板
        $source = admin_extension_path('organization/database/template');
        $target = public_extensions_path('organization/template');
        if (is_dir($source) && ! is_dir($target)) {
            File::copyDirectory($source, $target); // 复制整个目录及其内容到目标目录
        }
    }

    public function settingForm(): Form
    {
        return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(),
        ]);
    }
}
