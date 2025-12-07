<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Grade;
use DagaSmart\Organization\Models\Enterprise;
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
        if (!empty($data['enterprise_grade'])) {
            //学段年级
            $enterprise_grade = explode(',', $data['enterprise_grade']);
            //获取年级学段
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
        $type = is_school_module() ? 'school' : 'default';
        $model = new Stage;
        return $model->query()
            ->where('type', $type)
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
