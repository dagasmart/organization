<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Models\Department;
use DagaSmart\Organization\Models\Job;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\Patriarch;
use Illuminate\Database\Eloquent\Builder;


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
            $query->when($module, function ($query) use($module) {
                $query->where('module', $module);
            })->when($mer_id, function ($query) use($mer_id) {
                $query->where('mer_id', $mer_id);
            });
        })->with(['child']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
//        $query->whereHas('enterprise', function (Builder $builder) {
//            $enterprise_id = request('enterprise_id');
//            $builder->when($enterprise_id, function (Builder $builder) use (&$enterprise_id) {
//                if (!is_array($enterprise_id)) {
//                    $enterprise_id = explode(',', $enterprise_id);
//                }
//                $builder->whereIn('enterprise_id', $enterprise_id);
//            });
//            $department_id = request('department_id');
//            $builder->when($department_id, function (Builder $builder) use (&$department_id) {
//                if (!is_array($department_id)) {
//                    $department_id = explode(',', $department_id);
//                }
//                $builder->whereIn('department_id', $department_id);
//            });
//            $job_id = request('job_id');
//            $builder->when($job_id, function (Builder $builder) use (&$job_id) {
//                if (!is_array($job_id)) {
//                    $job_id = explode(',', $job_id);
//                }
//                $builder->whereIn('job_id', $job_id);
//            });
//        });
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->getModel()->getKeyName(), 'asc');
        }
    }

    public function list(): array
    {
        $list = parent::list();
        if ($list['items']) {
            foreach ($list['items'] as &$item) {
                $property = [];
                if ($item['child']) {
                    foreach ($item['child'] as &$child) {
                        if ($child['rel']) {
                            $property[] = [
                                'label' => [
                                    'type' => 'avatar',
                                    'src' => $child['rel']['student']['avatar'],
                                    'size' => 'small',
                                    'onEvent' => [
                                        'click' => [
                                            'actions' => [
                                                [
                                                    'actionType' => 'dialog',
                                                    'dialog' => [
                                                        'title' => '关联学生信息',
                                                        'actions' => [],
                                                        'closeOnEsc' => true, //esc键关闭
                                                        'closeOnOutside' => true, //域外可关闭
                                                        'showCloseButton' => true, //显示关闭
                                                        'body' => [
                                                            'type' => 'page',
                                                            'body' => [
                                                                [
                                                                    'type' => 'group',
                                                                    'title' => false,
                                                                    'mode' => 'horizontal',
                                                                    'actions' => [],
                                                                    'body' => [
                                                                        [
                                                                            'type' => 'group',
                                                                            'title' => false,
                                                                            'direction' => 'vertical',
                                                                            'columnRatio' => 7,
                                                                            'body' => [
                                                                                [
                                                                                    'type' => 'input-text',
                                                                                    'label' => '学生姓名',
                                                                                    'static' => true,
                                                                                    'value' => $child['rel']['student']['student_name']
                                                                                ],
                                                                                [
                                                                                    'type' => 'input-text',
                                                                                    'label' => '身份证号',
                                                                                    'static' => true,
                                                                                    'value' => $child['rel']['student']['id_card']
                                                                                ],
                                                                                [
                                                                                    'type' => 'input-text',
                                                                                    'label' => '国网学籍号',
                                                                                    'static' => true,
                                                                                    'value' => 'G' . $child['rel']['student']['id_card']
                                                                                ],
                                                                            ]
                                                                        ],
                                                                        [
                                                                            'type' => 'group',
                                                                            'title' => false,
                                                                            'direction' => 'horizontal',
                                                                            'columnRatio' => 5,
                                                                            'body' => [
                                                                                [
                                                                                    'type' => 'static-image',
                                                                                    'value' => $child['rel']['student']['avatar'],
                                                                                    'thumbRatio' => '1:1',
                                                                                    'thumbMode' => 'cover h-full rounded-md overflow-hidden',
                                                                                    'className' => 'h-full overflow-hidden',
                                                                                    'imageClassName' => 'w-52 h-64 overflow-hidden',
                                                                                    'fixedSizeClassName' => 'w-52 h-64 overflow-hidden',
                                                                                    'fixedSize' => true,
                                                                                    'crop' => ['aspectRatio' => '0.81'],
                                                                                ]
                                                                            ]
                                                                        ],
                                                                    ],

                                                                ],
                                                                ['type' => 'divider'],
                                                                [
                                                                    'type' => 'group',
                                                                    'title' => false,
                                                                    'mode' => 'horizontal',
                                                                    'body' => [
                                                                        [
                                                                            'type' => 'input-text',
                                                                            'label' => '就读学校',
                                                                            'static' => true,
                                                                            'value' => $child['rel']['enterprise']['enterprise_name'] . ' / ' . $child['rel']['grade']['grade_name'] . ' / ' . $child['rel']['classes']['classes_name']
                                                                        ],
                                                                    ]
                                                                ],
                                                                ['type' => 'divider'],
                                                                [
                                                                    'type' => 'group',
                                                                    'title' => false,
                                                                    'mode' => 'horizontal',
                                                                    'body' => [
                                                                        [
                                                                            'type' => 'input-text',
                                                                            'label' => '性别',
                                                                            'value' => $child['rel']['student']['sex_as'],
                                                                            'static' => true,
                                                                        ],
                                                                        [
                                                                            'type' => 'input-text',
                                                                            'label' => '民族',
                                                                            'value' => $child['rel']['student']['nation_as'],
                                                                            'static' => true,
                                                                        ],
                                                                        [
                                                                            'type' => 'select',
                                                                            'label' => '状态',
                                                                            'static' => true,
                                                                            'options' => Enum::StudentState,
                                                                            'value' => $child['rel']['student']['state']
                                                                        ],
                                                                    ]
                                                                ],
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                            ];
                        }
                    }
                }
                $item['property'] = $property;
            }
        }
        return $list;
    }


    public function store($data): bool
    {
        $id = $data['id'] ?? null;
        if ($id) {
            $data = array_intersect_key($data, array_flip(['id','id_card', 'combo'])) ?? null;
            admin_abort_if(!$data, '职务信息不能为空');
            return $this->update($id, $data);
        } else {
            unset($data['id']);
            return parent::store($data);
        }
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        //手机号码
        $mobile = $data['mobile'] ?? null;
        if ($mobile && strpos($mobile, '*')) {
            unset($data['mobile']);
        }

        admin_abort_if(empty($data['id_card']), '请输入有效身份证号');
        //身份证号
        $id_card = $data['id_card'] ?? null;
        if ($id_card) {
            if (strpos($id_card, '*')) {
                unset($data['id_card']);
            } else {
                //身份证号校验
                identifyByIdCard($id_card);
                //是否已存在
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
        //模块
        if (admin_current_module()) {
            $data['module'] = admin_current_module();
        }
        //商户
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
                    $row['worker_sn'] = $enterprise_id . $worker_id;
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
    public function enterpriseData(): \Illuminate\Support\Collection
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
     * @return array
     */
    public function getEnterpriseAll(): array
    {
        $model = new Enterprise;
        return $model->query()
            ->whereNull('deleted_at')
            ->get(['id as value','enterprise_name as label'])
            ->toArray();
    }

    /**
     * 部门列表
     * @return array
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
        //Job::initialize();
        $list = Job::query()
            ->select(admin_raw('*, job_name as label, id as value'))
            ->orderBy('sort')
            ->get()
            ->toArray();
        return array2tree($list, 0);
    }

}
