<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Grade;
use DagaSmart\Organization\Models\Enterprise;
use Illuminate\Database\Eloquent\Builder;

/**
 * 基础-年级服务类
 *
 * @method Grade getModel()
 * @method Grade|Builder query()
 */
class GradeService extends AdminService
{
	protected string $modelName = Grade::class;

    /**
     * 机构年级列表
     * @param int $school_id
     * @return array
     */
    public function EnterpriseGrade(int $school_id): array
    {
        $schoolGrade = [];
        if ($school_id) {
            $enterprise_grade = Enterprise::query()->where('id', $school_id)->value('enterprise_grade');
            $schoolGrade = array_filter(explode(',', $enterprise_grade));
        }
        $model = new Grade;
        $data = $model->query()
            ->whereIn('id', $schoolGrade)
            ->get(['id as value','grade_name as label', 'id', 'parent_id'])
            ->toArray();
        return array2tree($data);
    }

}
