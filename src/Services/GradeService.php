<?php

namespace DagaSmart\School\Services;

use DagaSmart\School\Models\Grade;
use DagaSmart\School\Models\School;
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
     * 学校年级列表
     * @param int $school_id
     * @return array
     */
    public function SchoolGrade(int $school_id): array
    {
        $schoolGrade = [];
        if ($school_id) {
            $school_grade = School::query()->where('id', $school_id)->value('school_grade');
            $schoolGrade = array_filter(explode(',', $school_grade));
        }
        $model = new Grade;
        $data = $model->query()
            ->whereIn('id', $schoolGrade)
            ->get(['id as value','grade_name as label', 'id', 'parent_id'])
            ->toArray();
        return array2tree($data);
    }

}
