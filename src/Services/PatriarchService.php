<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Department;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\Job;
use DagaSmart\Organization\Models\Patriarch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 基础-家长服务类
 *
 * @method Patriarch getModel()
 * @method Patriarch|Builder query()
 */
class PatriarchService extends AdminService
{
    protected string $modelName = Patriarch::class;

    public function loadRelations($query): void
    {
        $query->whereHas('child', function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->when($module, function ($query) use ($module) {
                $query->where('module', $module);
            })->when($mer_id, function ($query) use ($mer_id) {
                $query->where('mer_id', $mer_id);
            });
        })->with(['child']);
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->getModel()->getKeyName(), 'asc');
        }
    }

    /**
     * 获取列表并注入 AMIS 学生详情弹窗
     */
    public function list(): array
    {
        $list = parent::list();

        if (empty($list['items'])) {
            return $list;
        }

        foreach ($list['items'] as &$item) {
            $childes = [];
            $property = [];

            // 安全提取子级数据
            $children = $item['child'] ?? [];
            unset($item['child']);

            foreach ($children as $child) {
                $rel = $child['rel'] ?? null;
                if (! $rel) {
                    continue;
                }

                // 【核心修复】统一将 Model/Array 转为纯数组，避免后续类型错误
                $relData = $this->normalizeRelData($rel);

                if (empty($relData['student'])) {
                    continue;
                }

                $childes[] = $relData;
                $property[] = [
                    'label' => [
                        'type' => 'avatar',
                        'src' => $relData['student']['avatar'] ?? '',
                        'size' => 'small',
                        'onEvent' => [
                            'click' => [
                                'actions' => [
                                    [
                                        'actionType' => 'dialog',
                                        'dialog' => $this->buildStudentDetailDialog($relData),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $item['childes'] = $childes;
            $item['property'] = $property;
        }
        unset($item); // 释放引用

        return $list;
    }

    /**
     * 将 rel 数据统一标准化为数组
     * 兼容 Eloquent Model、stdClass、普通数组
     */
    private function normalizeRelData(mixed $rel): array
    {
        if ($rel instanceof Collection) {
            return $rel->toArray();
        }

        if (is_object($rel)) {
            return json_decode(json_encode($rel), true);
        }

        return is_array($rel) ? $rel : [];
    }

    /**
     * 构建学生详情弹窗 AMIS Schema
     */
    private function buildStudentDetailDialog(array $rel): array
    {
        $student = $rel['student'] ?? [];
        $enterprise = $rel['enterprise'] ?? [];
        $grade = $rel['grade'] ?? [];
        $classes = $rel['classes'] ?? [];

        $avatar = $student['avatar'] ?? null;
        $id_card = $student['id_card'] ?? null;
        $student_name = $student['student_name'] ?? null;
        $student_code = $student['student_code'] ?? null;
        $sex_as = $student['sex_as'] ?? null;
        $nation_as = $student['nation_as'] ?? null;
        $state_as = $rel['state_as'] ?? null;

        // 拼接学校信息，自动过滤空段
        $schoolInfo = implode(' / ', array_filter([
            $enterprise['enterprise_name'] ?? null,
            $grade['grade_name'] ?? null,
            $classes['classes_name'] ?? null,
        ]));

        return [
            'title' => '关联学生信息',
            'actions' => [],
            'closeOnEsc' => true,
            'closeOnOutside' => true,
            'showCloseButton' => true,
            'body' => [
                'type' => 'page',
                'body' => [
                    // === 基本信息 + 照片 ===
                    [
                        'type' => 'group',
                        'title' => false,
                        'mode' => 'horizontal',
                        'body' => [
                            [
                                'type' => 'group',
                                'title' => false,
                                'direction' => 'vertical',
                                'columnRatio' => 7,
                                'body' => [
                                    ['type' => 'input-text', 'label' => '学生姓名', 'static' => true, 'value' => $student_name],
                                    // 直接使用原始身份证号，不做脱敏
                                    ['type' => 'input-text', 'label' => '身份证号', 'static' => true, 'value' => $id_card],
                                    ['type' => 'input-text', 'label' => '国网学籍', 'static' => true, 'value' => $student_code],
                                ],
                            ],
                            [
                                'type' => 'group',
                                'title' => false,
                                'direction' => 'horizontal',
                                'columnRatio' => 5,
                                'body' => [
                                    [
                                        'type' => 'static-image',
                                        'value' => $avatar,
                                        'thumbRatio' => '1:1',
                                        'thumbMode' => 'cover h-full rounded-md overflow-hidden',
                                        'className' => 'h-full overflow-hidden',
                                        'imageClassName' => 'w-52 h-64 overflow-hidden',
                                        'fixedSizeClassName' => 'w-52 h-64 overflow-hidden',
                                        'fixedSize' => true,
                                        'crop' => ['aspectRatio' => '0.81'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    ['type' => 'divider'],
                    // === 就读学校 ===
                    [
                        'type' => 'group',
                        'title' => false,
                        'mode' => 'horizontal',
                        'body' => [
                            ['type' => 'input-text', 'label' => '就读学校', 'static' => true, 'value' => $schoolInfo],
                        ],
                    ],
                    ['type' => 'divider'],
                    // === 其他属性 ===
                    [
                        'type' => 'group',
                        'title' => false,
                        'mode' => 'horizontal',
                        'body' => [
                            ['type' => 'input-text', 'label' => '性别', 'static' => true, 'value' => $sex_as],
                            ['type' => 'input-text', 'label' => '民族', 'static' => true, 'value' => $nation_as],
                            ['type' => 'input-text', 'label' => '状态', 'static' => true, 'value' => $state_as],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function store($data): bool
    {
        $id = $data['id'] ?? null;
        if ($id) {
            $data = array_intersect_key($data, array_flip(['id', 'id_card', 'combo'])) ?? null;
            admin_abort_if(! $data, '职务信息不能为空');

            return $this->update($id, $data);
        } else {
            unset($data['id']);

            return parent::store($data);
        }
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        // 手机号码
        $mobile = $data['mobile'] ?? null;
        if ($mobile && strpos($mobile, '*')) {
            unset($data['mobile']);
        }

        admin_abort_if(empty($data['id_card']), '请输入有效身份证号');
        // 身份证号
        $id_card = $data['id_card'] ?? null;
        if ($id_card) {
            if (strpos($id_card, '*')) {
                unset($data['id_card']);
            } else {
                // 身份证号校验
                identifyByIdCard($id_card);
                // 是否已存在
                $id = $data['id'] ?? null;
                $exists = Patriarch::query()
                    ->where(['id_card' => $id_card])
                    ->when($id, function ($query) use ($id) {
                        return $query->where('id', '<>', $id);
                    })
                    ->exists();
                admin_abort_if($exists, '身份证号(${id_card})已存在，请检查');
            }
        }
        // 模块
        if (admin_current_module()) {
            $data['module'] = admin_current_module();
        }
        // 商户
        if (admin_mer_id()) {
            $data['mer_id'] = admin_mer_id();
        }
    }

    public function saved($model, $isEdit = false): void
    {
        $combo = $this->request->combo ?? null;
        if ($model && $combo) {
            $current = [];
            array_walk($combo, function ($item) use ($model, &$current) {
                $jobs = explode(',', $item['job_id']);
                array_walk($jobs, function ($value) use ($model, $item, &$current) {
                    $enterprise_id = $item['enterprise_id'];
                    $department_id = $item['department_id'];
                    $worker_id = $model->id;
                    $module = $item['module'] ?? admin_current_module();
                    $mer_id = $item['mer_id'] ?? admin_mer_id();
                    $row = [];
                    $row['enterprise_id'] = $enterprise_id;
                    $row['department_id'] = $department_id;
                    $row['job_id'] = $value;
                    $row['worker_id'] = $worker_id;
                    $row['worker_sn'] = $enterprise_id.$worker_id;
                    $row['module'] = $module;
                    $row['mer_id'] = $mer_id;
                    $current[] = $row;
                    EnterpriseDepartmentJobWorker::query()->where($row)->forceDelete();
                });
            });
            $model->enterpriseJobs()->sync($current);
        }
    }

    /**
     * 机构列表
     */
    public function enterpriseData(): Collection
    {
        return $this->getModel()->enterpriseData();
    }

    public function EnterprisePatriarchCheck($id_card)
    {
        return $this->query()
            ->with(['child'])
            ->where(['id_card' => $id_card])
            ->first();
    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        $model = new Enterprise;

        return $model->query()
            ->whereNull('deleted_at')
            ->get(['id as value', 'enterprise_name as label'])
            ->toArray();
    }

    /**
     * 部门列表
     */
    public function getDepartmentAll(): array
    {
        $model = new Department;
        $res = $model->query()
            ->select(admin_raw('*, department_name as label, id as value'))
            ->orderBy('sort')
            ->get()
            ->toArray();

        return array2tree($res, 0);
    }

    /**
     * 职务列表
     */
    public function getJobAll(): array
    {
        // Job::initialize();
        $list = Job::query()
            ->select(admin_raw('*, job_name as label, id as value'))
            ->orderBy('sort')
            ->get()
            ->toArray();

        return array2tree($list, 0);
    }
}
