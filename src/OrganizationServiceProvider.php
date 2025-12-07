<?php

namespace DagaSmart\Organization;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\TextControl;
use DagaSmart\BizAdmin\Extend\ServiceProvider;
use Exception;

class OrganizationServiceProvider extends ServiceProvider
{

    protected $menu;
    protected function menu(): array
    {
        $menu = [];
        $menu[] = [
            [
                'parent' => NULL,
                'title' => '基础维护',
                'url' => '/biz/enterprise',
                'url_type' => 1,
                'icon' => 'carbon:calendar-settings',
            ],
            [
                'parent' => '基础维护',
                'title' => '机构管理',
                'url' => '/biz/enterprise/index',
                'url_type' => 1,
                'icon' => 'teenyicons:school-outline',
            ],
            [
                'parent' => '基础维护',
                'title' => '员工管理',
                'url' => '/biz/enterprise/worker',
                'url_type' => 1,
                'icon' => 'la:chalkboard-teacher',
            ],
        ];
        if (is_school_module()) {
            $menu[] = [
                [
                    'parent' => '基础维护',
                    'title' => '学生管理',
                    'url' => '/biz/enterprise/student',
                    'url_type' => 1,
                    'icon' => 'ph:student-light',
                ],
            ];
        }
        $menu[] = [
            [
                'parent' => '基础维护',
                'title' => '基础设施',
                'url' => '/biz/enterprise/facility',
                'url_type' => 1,
                'icon' => 'heroicons:building-office-2',
            ],
            [
                'parent' => '基础维护',
                'title' => '设备管理',
                'url' => '/biz/enterprise/device',
                'url_type' => 1,
                'icon' => 'ph:devices-light',
            ],

        ];
        return $this->menu = $menu;
    }


    /**
     * @return void
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
    }


	public function settingForm(): Form
    {
	    return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(),
	    ]);
	}
}
