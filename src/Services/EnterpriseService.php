<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartment;
use DagaSmart\Organization\Models\EnterpriseDepartmentJob;
use DagaSmart\Organization\Models\Grade;
use DagaSmart\Organization\Models\Nature;
use DagaSmart\Organization\Models\Stage;
use Illuminate\Database\Eloquent\Builder;

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
        // $query->with('authorize');
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
        $credit_code = $data['credit_code'] ?? null;
        if ($credit_code) {
            $exists = $this->getModel()->query()
                ->where('credit_code', $credit_code)
                ->when($id, function ($builder) use ($id) {
                    return $builder->where('id', '!=', $id);
                })
                ->exists();
            if ($exists) {
                admin_abort('当前机构信用代码已被占用，请检查重试');
            }
        }
        // 地区代码
        $data['region'] = is_array($data['region']) ? $data['region']['code'] : $data['region'];
        // 模块
        if (admin_current_module()) {
            $data['module'] = admin_current_module();
        }
        // 商户
        if (admin_mer_id()) {
            $data['mer_id'] = admin_mer_id();
        }
    }

    /**
     * 开办模式(学段)列表
     */
    public function getStageAll(): array
    {
        $type = is_school_module() ? 'school' : 'company';
        $model = new Stage;

        return $model->query()
            ->where('type', $type)
            ->orderBy('sort')
            ->get(['id as value', 'stage_name as label'])
            ->toArray();
    }

    /**
     * 年级列表
     */
    public function getGradeAll(): array
    {
        $model = new Grade;
        $data = $model->query()->get(['id as value', 'grade_name as label', 'id', 'parent_id'])->toArray();

        return array2tree($data);
    }

    /**
     * 机构列表
     */
    public function natureOption(): array
    {
        $model = new Nature;

        return $model->query()
            ->select('value', 'label')
            ->where(function (Builder $builder) {
                if (is_school_module()) {
                    $builder->whereIn('type', ['school']);
                } else {
                    $builder->whereNotIn('type', ['school']);
                }
            })
            ->get()
            ?->toArray();
    }

    /**
     * 开办模式列表
     */
    public function stageOption(): array
    {
        $id = request()->nature_id ?? 0;
        $model = new Nature;
        $type = $model->query()->where(['id' => $id])->value('type');
        $model = new Stage;

        return $model->query()
            ->select('id as value', 'stage_name as label')
            ->when($type, function ($builder) use ($type) {
                $builder->where(['type' => $type]);
            })
            ->get()
            ?->toArray();
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
            //->where('enterprise_id', $enterprise_id)
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

        $id = $input['id'] ?? null; //通过id判断编辑操作

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

        $id = $input['id'] ?? null; //通过id判断编辑操作

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
        admin_abort_if(!$id, 'id参数必填');
        $model = new EnterpriseDepartment;
        return $model->query()->where(['id' => $id])->delete();
    }

    public function jobDelete()
    {
        $id = request()->id ?? null;
        admin_abort_if(!$id, 'id参数必填');
        $model = new EnterpriseDepartmentJob;
        return $model->query()->where(['id' => $id])->delete();
    }



}
