<?php

namespace DagaSmart\School\Services;

use DagaSmart\School\Models\Classes;
use DagaSmart\School\Models\EnterpriseGradeClasses;
use Illuminate\Database\Eloquent\Builder;


/**
 * 基础-部门服务类
 *
 * @method Classes getModel()
 * @method Classes|Builder query()
 */
class DepartmentService extends AdminService
{
	protected string $modelName = Classes::class;


    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->primaryKey(), 'asc');
        }
    }

    /**
     * 新增或修改后更新关联数据
     * @param $model
     * @param bool $isEdit
     * @return void
     */
    public function saved($model, $isEdit = false): void
    {
        parent::saved($model, $isEdit);
        $request = request()->all();
        $data = [
            'school_id' => $request['school_id'],
            'grade_id' => $request['grade_id'],
            'classes_id' => $model->id
        ];
        admin_transaction(function () use ($data) {
            if ($data['classes_id']) {
            EnterpriseGradeClasses::query()->where('classes_id', $data['classes_id'])->delete();
            }
            EnterpriseGradeClasses::query()->insert($data);
        });
    }

    /**
     * 学校列表
     */
    public function getSchoolAll(): array
    {
        return (new StudentService)->getSchoolAll();
    }

    /**
     * 学校年级列表
     * @param int $school_id
     * @param $grade_id
     * @return array
     */
    public function SchoolGradeClasses(int $school_id, $grade_id): array
    {
        $classes_id = EnterpriseGradeClasses::query()
            ->where('school_id', $school_id)
            ->where('grade_id', $grade_id)
            ->pluck('classes_id')
            ->unique()
            ->filter()
            ->toArray();
        return Classes::query()
            ->whereIn('id', $classes_id)
            ->get(['id as value','classes_name as label'])
            ->toArray();
    }

}
