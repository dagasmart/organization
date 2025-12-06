<?php

namespace DagaSmart\School\Services;

use DagaSmart\School\Models\Grade;
use DagaSmart\School\Models\School;
use DagaSmart\School\Models\Stage;
use Illuminate\Database\Eloquent\Builder;

/**
 * 基础-学校服务类
 *
 * @method School getModel()
 * @method School|Builder query()
 */
class SchoolService extends AdminService
{
	protected string $modelName = School::class;


    public function addRelations($query, string $scene = 'list'): void
    {
        //$query->with('authorize');
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
        if (!empty($data['school_grade'])) {
            //学段年级
            $school_grade = explode(',', $data['school_grade']);
            //获取年级学段
            $parent = Grade::query()
                ->whereIn('id', $school_grade)
                ->distinct()
                ->pluck('parent_id')
                ->filter()
                ->unique()
                ->toArray();
            $data['school_grade'] = admin_sort(array_unique(array_merge($parent, $school_grade)), 'desc');
        }
        $id = $data['id'] ?? null;
        $school_name = $data['school_name'] ?? null;
        if ($school_name) {
            $exists = $this->getModel()->query()
                ->where('school_name', $school_name)
                ->when($id, function ($builder) use ($id) {
                    return $builder->where('id', '!=', $id);
                })
                ->exists();
            if ($exists) {
                admin_abort('当前学校名称已存在，请检查重试');
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
                admin_abort('当前学校信用代码已被占用，请检查重试');
            }
        }
        //地区代码
        $data['region'] = is_array($data['region']) ? $data['region']['code'] : $data['region'];
        //模块
        if (admin_current_module()) {
            $data['module'] = admin_current_module();
        }
        //商户
        if (admin_mer_id()) {
            $data['mer_id'] = admin_mer_id();
        }
    }

    /**
     * 学段列表
     * @return array
     */
    public function getStageAll(): array
    {
        $model = new Stage;
        return $model->query()
            ->orderBy('sort')
            ->get(['id as value', 'stage_name as label'])
            ->toArray();
    }

    /**
     * 年级列表
     * @return array
     */
    public function getGradeAll(): array
    {
        $model = new Grade;
        $data = $model->query()->get(['id as value','grade_name as label', 'id', 'parent_id'])->toArray();
        return array2tree($data);
    }

}
