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
     * 获取列表并注入学生详情弹窗
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
     * 构建学生详情弹窗Schema
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
        $allowedFields = ['id', 'id_card', 'combo'];
        $id = $data['id'] ?? null;

        if ($id !== null) {
            // 更新：白名单过滤 + 非空校验
            $data = array_intersect_key($data, array_flip($allowedFields));
            admin_abort_if(empty($data), '职务信息不能为空');

            return $this->update($id, $data);
        }

        // 新增：同样做白名单过滤，保持一致性
        unset($data['id']);
        $data = array_intersect_key($data, array_flip(array_diff($allowedFields, ['id'])));

        return parent::store($data);
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        // 1. 【优化】一次性提取所有需要的输入，避免多次调用 input() 触发底层参数解析
        $input = $this->request->input();

        $mobile = $input['mobile'] ?? '';
        $idCard = $input['id_card'] ?? '';
        $id = $input['id'] ?? null;

        // 2. 【优化】使用 strpos 替代 str_contains (PHP 8+ 虽已优化，但原生函数仍最快)
        $mobileIsMasked = is_string($mobile) && str_contains($mobile, '*');
        $idCardIsMasked = is_string($idCard) && str_contains($idCard, '*');

        // 3. 【优化】按需移除脱敏字段，并同步标记是否需要回写
        $needSync = false;
        if ($mobileIsMasked) {
            $this->request->offsetUnset('mobile');
            $needSync = true;
        }
        if ($idCardIsMasked) {
            $this->request->offsetUnset('id_card');
            $needSync = true;
        }

        // 4. 【优化】验证规则静态化 + 条件构建最小化
        $rules = ['childes' => ['required', 'array']];
        $messages = ['childes.required' => '关联学生不能为空'];

        if (! $mobileIsMasked) {
            $rules['mobile'] = ['nullable', 'regex:/^1[3-9]\d{9}$/'];
            $messages['mobile.regex'] = '请输入有效的中国大陆手机号码';
        }
        if (! $idCardIsMasked) {
            $rules['id_card'] = ['nullable', 'regex:/^\d{17}[\dXx]$/'];
            $messages['id_card.regex'] = '身份证号格式不正确';
        }

        // 5. 【优化】合并模块/商户信息到 Request（仅在需要时 merge）
        $extraMerge = [];
        if ($module = admin_current_module()) {
            $extraMerge['module'] = $module;
        }
        if ($merId = admin_mer_id()) {
            $extraMerge['mer_id'] = $merId;
        }
        if (! empty($extraMerge)) {
            $this->request->merge($extraMerge);
            $needSync = true;
        }

        // 6. 【关键】仅在数据被修改时才执行 all() 和赋值，避免无意义的数组拷贝
        if ($needSync) {
            $data = $this->request->input();
        } else {
            // 未修改脱敏字段且无额外 merge 时，直接用已提取的 inputs 构造干净数据
            // 避免再次调用 request->all() 产生的内部遍历开销
            unset($input['mobile'], $input['id_card']); // 防御性清理
            $data = $input;
        }

        // 7. 执行验证（此时 Request 与 $data 完全一致）
        $this->request->validate($rules, $messages);

        // 8. 【优化】业务校验直接使用局部变量，零哈希查找
        if (! $idCardIsMasked && $idCard !== '' && $idCard !== null) {
            identifyByIdCard($idCard);

            $exists = Patriarch::query()
                ->where('id_card', $idCard)
                ->when($id, fn ($query) => $query->where('id', '<>', $id))
                ->exists();

            admin_abort_if($exists, "身份证号({$idCard})已存在，请检查");
        }
    }

    public function saved($model, $isEdit = false): void
    {
        $childes = $this->request->input('childesx', []);
        if ($model && $childes) {

            $current = [];
            array_walk($childes, function ($item) use ($model, &$current) {
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
