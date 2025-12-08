<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Classes;
use DagaSmart\Organization\Models\EnterpriseGradeClasses;
use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
use Illuminate\Database\Eloquent\Builder;


/**
 * 基础-班级服务类
 *
 * @method Classes getModel()
 * @method Classes|Builder query()
 */
class ClassesService extends AdminService
{
	protected string $modelName = Classes::class;

    public function loadRelations($query): void
    {
        $query->with(['enterprise','rel']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('enterprise', function (Builder $builder) {
            $school_id = request('enterprise_id');
            $builder->when($school_id, function (Builder $builder) use (&$school_id) {
                if (!is_array($school_id)) {
                    $school_id = explode(',', $school_id);
                }
                $builder->whereIn('enterprise_id', $school_id);
            });
            $grade_id = request('grade_id');
            $builder->when($grade_id, function (Builder $builder) use (&$grade_id) {
                if (!is_array($grade_id)) {
                    $grade_id = explode(',', $grade_id);
                }
                $builder->whereIn('grade_id', $grade_id);
            });
            $classes_id = request('classes_id');
            $builder->when($classes_id, function (Builder $builder) use (&$classes_id) {
                if (!is_array($classes_id)) {
                    $classes_id = explode(',', $classes_id);
                }
                $builder->whereIn('job_id', $classes_id);
            });
        });
    }

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
            'enterprise_id' => $request['enterprise_id'],
            'grade_id' => $request['grade_id'],
            'classes_id' => $model->id
        ];
        admin_transaction(function () use ($data) {
            if ($data['classes_id']) {
                EnterpriseGradeClasses::query()->where($data)->delete();
            }
            EnterpriseGradeClasses::query()->insert($data);
        });
    }

    public function deleting($ids)
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        admin_abort_if(!$ids, '请选择删除项');
        //获取存在学生的班级id组
        $oids = EnterpriseGradeClassesStudent::query()
            ->whereIn('classes_id', $ids)
            ->pluck('classes_id')
            ->toArray();
        //获取没有学生的班级id组
        $ids = array_diff($ids, $oids);
        admin_abort_if($oids && !$ids, '当前勾选班级存在学生信息，无法删除');
        EnterpriseGradeClasses::query()->whereIn('classes_id', $ids)->delete();
        return implode(',', $ids);
    }

    /**
     * 学校列表
     */
    public function getEnterpriseAll(): array
    {
        return (new StudentService)->getEnterpriseAll();
    }

    /**
     * 学校年级列表
     * @param int $school_id
     * @param $grade_id
     * @return array
     */
    public function enterpriseGradeClasses(int $school_id, $grade_id): array
    {
        $classes_id = EnterpriseGradeClasses::query()
            ->where('enterprise_id', $school_id)
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
