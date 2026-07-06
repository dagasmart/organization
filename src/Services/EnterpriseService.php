<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\BizAdmin\Models\BasicRegion;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseBind;
use DagaSmart\Organization\Models\EnterpriseDepartment;
use DagaSmart\Organization\Models\EnterpriseDepartmentJob;
use DagaSmart\Organization\Models\Grade;
use DagaSmart\Organization\Models\Nature;
use DagaSmart\Organization\Models\Stage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * 基础-机构服务类
 *
 * @method Enterprise getModel()
 * @method Enterprise|Builder query()
 */
class EnterpriseService extends AdminService
{
    protected string $modelName = Enterprise::class;

    public function addRelations($query, string $scene = 'list'): void
    {
        $query->whereHas('bind');
    }

    public function searchable($query): void
    {

        $code = request()->region ?? null;

        if ($code) {
            $region = BasicRegion::query()
                ->when($code, function ($builder) use ($code) {
                    $builder->where('code', $code);
                })
                ->select('id', 'parent_id', 'code')
                ->with('children.children.children')
                ->get();
            $code = array2code($region, 'code');
            // 追加参数
            request()->merge(['region' => implode(',', $code)]);
        }

        parent::searchable($query);

    }

    public function array2code($data = [], &$res = []): array
    {
        if ($data) {
            foreach ($data as $item) {
                $res[] = $item->code;
                if ($item->children) {
                    $this->array2code($item->children, $res);
                }
            }
        }

        return $res;
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->getModel()->getKeyName(), 'asc');
        }
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        $data = clear_array_trim($data);
        if (! empty($data['enterprise_grade'])) {
            // 学段年级
            $enterprise_grade = explode(',', $data['enterprise_grade']);
            // 获取年级学段
            $parent = Grade::query()
                ->whereIn('id', $enterprise_grade)
                ->distinct()
                ->pluck('parent_id')
                ->filter()
                ->unique()
                ->toArray();
            $data['enterprise_grade'] = admin_sort(array_unique(array_merge($parent, $enterprise_grade)), 'desc');
        }
        $id = $data['id'] ?? null;
        $enterprise_name = $data['enterprise_name'] ?? null;
        if ($enterprise_name) {
            $exists = $this->getModel()->query()
                ->where('enterprise_name', $enterprise_name)
                ->when($id, function ($builder) use ($id) {
                    return $builder->where('id', '!=', $id);
                })
                ->exists();
            if ($exists) {
                admin_abort('当前机构名称已存在，请检查重试');
            }
        }
        $social_credit_code = $data['social_credit_code'] ?? null;
        if ($social_credit_code) {
            $exists = $this->getModel()->query()
                ->where('social_credit_code', $social_credit_code)
                ->when($id, function ($builder) use ($id) {
                    return $builder->where('id', '!=', $id);
                })
                ->exists();
            if ($exists) {
                admin_abort('当前机构信用代码已被占用，请检查重试');
            }
        }
        // 地区代码
        if (! empty($data['region'])) {
            if (is_array($data['region'])) {
                $data['region'] = $data['region']['code'] ?? null;
            }
            // admin_region_code($data['region']); // 地区code更新缓存
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

        $grade = $model->enterprise_grade ?? null;

        // 防御性判断
        if (! $model || empty($grade)) {
            return;
        }

        $gradeIds = explode(',', $grade);

        sort($gradeIds); // 升序

        $data = [];
        $module = admin_current_module();
        $mer_id = admin_mer_id();

        // 1. 仅做数据组装，绝对不要在循环中执行数据库操作
        foreach ($gradeIds as $id) {
            $data[] = [
                'enterprise_id' => $model->id,
                'grade_id' => $id,
                'module' => $module,
                'mer_id' => $mer_id,
            ];
        }

        // 2. 使用事务保证数据一致性
        admin_transaction(function () use ($model, $data) {
            // 3. 直接调用 sync，Laravel 会自动比对差异并执行安全的增删操作
            // 注意：如果中间表没有唯一索引，sync 可能会报重复插入错误，需确保表结构正确
            $model->enterpriseGrade()->sync($data);
        });

    }

    /**
     * 批量删除企业（解绑优先，按需删主）
     *
     * @param  string  $ids  逗号分隔的企业ID字符串
     */
    public function delete(string $ids): array
    {
        // 1. 安全解析ID
        $ids = array_values(array_unique(array_filter(
            explode(',', $ids),
            fn ($v) => ctype_digit($v) && $v > 0 && strlen($v) <= 19
        )));

        if (empty($ids)) {
            admin_abort('删除机构ID不允许为空');
        }

        if (count($ids) > 200) {
            admin_abort('单次最多支持处理200条记录');
        }

        $mer_id = admin_mer_id();
        $module = admin_current_module();
        $isModuleAdmin = is_module_administrator();

        $deletedMainCount = 0;
        $deletedBindCount = 0;

        // 2. 事务内原子校验+删除（合并为单次查询，消除TOCTOU）
        admin_transaction(function () use ($ids, $module, $mer_id, $isModuleAdmin, &$deletedMainCount, &$deletedBindCount) {

            $bindModel = new EnterpriseBind;

            $externalQuery = $bindModel->query()->withoutGlobalScopes()->whereIn('enterprise_id', $ids);

            if ($isModuleAdmin) {
                // 【核心修复】模块管理员视角的外部关联：
                // ✅ 其他模块的任何绑定
                // ✅ 当前模块下 mer_id IS NOT NULL 的绑定（有明确商户归属 = 其他商户）
                // ✅ 当前模块下 mer_id IS NULL 的绑定 → 视为自身关联，不阻断
                $blockedIds = $externalQuery
                    ->where(function (Builder $q) use ($module) {
                        $q->whereNot('module', $module)
                            ->orWhere(function (Builder $sub) use ($module) {
                                $sub->where('module', $module)
                                    ->whereNotNull('mer_id');
                            });
                    })
                    ->lockForUpdate()
                    ->pluck('enterprise_id')
                    ->unique()
                    ->values()
                    ->all();
            } else {
                // 普通商户视角：其他模块 + 同模块非当前商户
                $blockedIds = $externalQuery
                    ->where(function (Builder $q) use ($module, $mer_id) {
                        $q->whereNot('module', $module)
                            ->orWhere(fn (Builder $sub) => $sub
                                ->where('module', $module)
                                ->where('mer_id', '!=', $mer_id)
                            );
                    })
                    ->lockForUpdate()
                    ->pluck('enterprise_id')
                    ->unique()
                    ->values()
                    ->all();
            }

            // 快速失败
            if (! empty($blockedIds) && $isModuleAdmin) {
                $sample = implode(', ', array_slice($blockedIds, 0, 5));
                $suffix = count($blockedIds) > 5 ? ' 等'.count($blockedIds).'家' : '';
                admin_abort("以下企业存在多个商户关联，无法直接删除：{$sample}{$suffix}");
            }

            // ✅ 校验通过，执行删除
            // 构建当前操作者的绑定删除条件
            $deleteQuery = $bindModel->newQuery()
                ->whereIn('enterprise_id', $ids)
                ->where('module', $module);

            if ($isModuleAdmin) {
                // 模块管理员：仅删除 mer_id IS NULL 的自身关联
                // （mer_id IS NOT NULL 的记录已被上方校验确认为不存在，但显式限定更安全）
                $deleteQuery->whereNull('mer_id');
            } else {
                $deleteQuery->where('mer_id', $mer_id);
            }

            $deletedBindCount = $deleteQuery->delete();

            // 删除主表
            if ($isModuleAdmin || $mer_id) {
                $deletedMainCount = $this->query()
                    ->whereIn('id', $ids)
                    ->when(
                        $mer_id,
                        fn ($q) => $q->where('creator_id', $mer_id)
                    )
                    ->delete();
            }
        });

        return [
            'success' => true,
            'message' => "删除成功：解绑 {$deletedBindCount} 条，删除企业 {$deletedMainCount} 家",
        ];
    }

    /**
     * 机构(全部)列表
     */
    public function getNatureAll(): array
    {
        return Nature::query()
            ->when(
                is_school_module(),
                fn ($query) => $query->where('type', 'school'),
                fn ($query) => $query->where('type', '!=', 'school')
            )
            ->orderBy('sort')
            ->get(['value', 'label'])
            ->toArray();
    }

    /**
     * 开办模式(学段)列表
     */
    public function getStageAll(): array
    {
        return Stage::query()
            ->when(
                is_school_module(),
                fn ($query) => $query->where('type', 'school'),
                fn ($query) => $query->where('type', '!=', 'school')
            )
            ->orderBy('sort')
            ->get(['id as value', 'stage_name as label'])
            ->toArray();
    }

    /**
     * 年级列表
     */
    public function getGradeAll(): array
    {
        // 1. 获取并校验请求参数
        $stage_id = (int) request('stage_id');
        if (empty($stage_id)) {
            return [];
        }

        // 2. 获取学段编号,找不到对应的或 stage_no 为空，直接返回
        $stage_no = Stage::query()->where(['id' => $stage_id])->value('stage_no');
        if (empty($stage_no)) {
            return [];
        }

        // 3. 解析阶段学段编号列表并过滤空值,找不到对应的或为空，直接返回
        $stageIds = array_filter(explode(',', (string) $stage_no), 'strlen');

        if (empty($stageIds)) {
            return [];
        }

        // 4. 查询年级数据
        $data = Grade::query()
            ->where(function ($query) use ($stageIds) {
                $query
                    ->whereIn('id', $stageIds)
                    ->orWhereIn('parent_id', $stageIds);
            })
            ->orderByRaw('id ASC, sort ASC')
            ->get(['id', 'parent_id', 'grade_name'])
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->grade_name,
                    'id' => $item->id,
                    'parent_id' => $item->parent_id,
                ];
            })
            ->toArray();

        // 5. 转换为树形结构并返回
        return array2treeUltimate($data);
    }

    /**
     * 机构列表(联动)
     */
    public function natureOption(): array
    {
        $id = (int) request('stage_id');

        return Nature::query()
            ->whereHas('relation', function ($builder) use ($id) {
                $builder
                    ->when(
                        $id,
                        fn ($query) => $query->where('id', $id),
                    )
                    ->when(
                        is_school_module(),
                        fn ($query) => $query->where('type', 'school'),
                        fn ($query) => $query->where('type', '!=', 'school')
                    );
            })
            ->orderBy('sort')
            ->get(['value', 'label'])
            ->toArray();
    }

    /**
     * 开办模式列表(联动)
     */
    public function stageOption(): array
    {
        $id = (int) request('nature_id');

        return Stage::query()
            ->whereHas('relation', function ($builder) use ($id) {
                $builder
                    ->when(
                        $id,
                        fn ($query) => $query->where('id', $id),
                    )
                    ->when(
                        is_school_module(),
                        fn ($query) => $query->where('type', 'school'),
                        fn ($query) => $query->where('type', '!=', 'school')
                    );
            })
            ->orderBy('sort')
            ->get(['id as value', 'stage_name as label'])
            ->toArray();

    }

    public function departmentData(): array
    {
        $enterprise_id = request()->enterprise_id ?? 0;
        $model = new EnterpriseDepartment;
        $data = $model->query()
            ->when($enterprise_id, function ($builder) use ($enterprise_id) {
                $builder->where('enterprise_id', $enterprise_id);
            })
            ->get()
            ?->toArray();

        return array2tree($data);
    }

    public function jobData(): array
    {
        $enterprise_id = request()->enterprise_id ?? 0;
        $model = new EnterpriseDepartmentJob;
        $data = $model->query()
            ->when($enterprise_id, function ($builder) use ($enterprise_id) {
                $builder->where('enterprise_id', $enterprise_id);
            })
            ->get()
            ?->toArray();

        return array2tree($data);
    }

    public function departmentJobData(): array
    {
        $enterprise_id = request()->enterprise_id ?? 0;
        $department_id = request()->department_id ?? 0;
        $model = new EnterpriseDepartmentJob;
        $data = $model->query()
            // ->where('enterprise_id', $enterprise_id)
            ->when($enterprise_id, function ($builder) use ($enterprise_id) {
                $builder->where('enterprise_id', $enterprise_id);
            })
            ->when($department_id, function ($builder) use ($department_id) {
                $builder->where('department_id', $department_id);
            })
            ->get()
            ?->toArray();

        return $data;
    }

    public function departmentSave(): bool|int
    {
        $input = request()->input();
        if (empty($input['enterprise_id'])) {
            admin_abort('enterprise_id参数必填');
        }

        $id = $input['id'] ?? null; // 通过id判断编辑操作

        $data = [
            'enterprise_id' => $input['enterprise_id'],
            'department_name' => $input['department_name'],
            'parent_id' => $input['parent_id'] ?? null,
        ];

        $model = new EnterpriseDepartment;

        $exists = $model->query()
            ->where($data)
            ->when($id, function ($builder) use ($id) {
                $builder->where('id', '!=', $id);
            })
            ->exists();
        admin_abort_if($exists, '部门已存在');

        $data['state'] = $input['department_state'] ?? 0;
        $data['sort'] = $input['department_sort'] ?? 10;
        $data['module'] = admin_current_module();
        $data['mer_id'] = admin_mer_id();

        if ($id) {
            $record = $model->query()->where(['id' => $id])->first();
            $record->department_name = $data['department_name'];
            $record->parent_id = $data['parent_id'];
            $record->state = $data['state'];
            $record->sort = $data['sort'];

            return $record->save();
        } else {
            return $model->query()->insertOrIgnore($data);
        }
    }

    public function jobSave(): bool|int
    {
        $input = request()->input();
        if (empty($input['enterprise_id'])) {
            admin_abort('enterprise_id参数必填');
        }

        $data = [
            'enterprise_id' => $input['enterprise_id'],
            'department_id' => $input['department_id'],
            'job_name' => $input['job_name'],
            'parent_id' => $input['parent_id'] ?? null,
        ];

        $model = new EnterpriseDepartmentJob;

        $exists = $model->query()->where($data)->exists();
        admin_abort_if($exists, '部门职务已存在');

        $data['state'] = $input['job_state'] ?? 0;
        $data['sort'] = $input['job_sort'] ?? 10;
        $data['module'] = admin_current_module();
        $data['mer_id'] = admin_mer_id();

        $id = $input['id'] ?? null; // 通过id判断编辑操作

        if ($id) {
            $record = $model->query()->where(['id' => $id])->first();
            $record->department_id = $data['department_id'];
            $record->job_name = $data['job_name'];
            $record->parent_id = $data['parent_id'];
            $record->state = $data['state'];
            $record->sort = $data['sort'];

            return $record->save();
        } else {
            return $model->query()->insertOrIgnore($data);
        }
    }

    public function departmentDelete()
    {
        $id = request()->id ?? null;
        admin_abort_if(! $id, 'id参数必填');
        $model = new EnterpriseDepartment;

        return $model->query()->where(['id' => $id])->delete();
    }

    public function jobDelete()
    {
        $id = request()->id ?? null;
        admin_abort_if(! $id, 'id参数必填');
        $model = new EnterpriseDepartmentJob;

        return $model->query()->where(['id' => $id])->delete();
    }

    public function regionAll(): Collection
    {
        $code = admin_region_code();

        $data = BasicRegion::query()
            ->where('parent_id', 0)
            ->when($code, function ($builder) use ($code) {
                $code = substr($code, 0, 2);
                $code = str_pad($code, 6, '0');
                $builder->where('code', $code);
            })
            ->get();

        return $data->load('children.children.children');
    }

    public function enterpriseCheck($social_credit_code): ?Enterprise
    {
        $row = $this->query()
            ->where(['social_credit_code' => $social_credit_code])
            ->first();

        if (! $row) {
            return null; // 显式返回 null，比返回 $row 更清晰
        }

        return $row;
    }
}
