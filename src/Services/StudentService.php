<?php

namespace DagaSmart\School\Services;

use DagaSmart\School\Models\Classes;
use DagaSmart\School\Models\Grade;
use DagaSmart\School\Models\School;
use DagaSmart\School\Models\SchoolGradeClassesStudent;
use DagaSmart\School\Models\Student;
use Illuminate\Database\Eloquent\Builder;
/**
 * 基础-学生服务类
 *
 * @method Student getModel()
 * @method Student|Builder query()
 */
class StudentService extends AdminService
{
	protected string $modelName = Student::class;

    public function loadRelations($query): void
    {
        $query->with(['rel']);
    }
    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('rel', function (Builder $builder) {
            $school_id = request('school_id');
            $builder->when($school_id, function (Builder $builder) use (&$school_id) {
                if (!is_array($school_id)) {
                    $school_id = explode(',', $school_id);
                }
                $builder->whereIn('school_id', $school_id);
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

    public function saving(&$data, $primaryKey = ''): void
    {
        //地区代码
        $region_id = $data['region_id'] ?? null;
        if ($region_id) {
            if (is_array($data['region_id'])) {
                $data['region_id'] = $data['region_id']['code'];
            }
        }
        //手机号码
        $mobile = $data['mobile'] ?? null;
        if ($mobile && strpos($mobile, '*')) {
            unset($data['mobile']);
        }
        //身份证号
        $id_card = $data['id_card'] ?? null;
        if ($id_card && strpos($id_card, '*')) {
            unset($data['id_card']);
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
            'classes_id' => $request['classes_id'],
            'student_id' => $model->id
        ];
        admin_transaction(function () use ($data) {
            if ($data['classes_id']) {
                SchoolGradeClassesStudent::query()->where('student_id', $data['student_id'])->delete();
            }
            SchoolGradeClassesStudent::query()->insert($data);
        });
    }

//    public function deleted($ids): void
//    {
//        $this->getModel()->rel_school_grade_classes_student()->whereIn($this->primaryKey(), $ids)->delete();
//    }

    /**
     * 学校列表
     * @return array
     */
    public function getSchoolAll(): array
    {
        $model = new School;
        return $model->query()->whereNull('deleted_at')->get(['id as value','school_name as label'])->toArray();
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

    /**
     * 班级列表
     * @return array
     */
    public function getClassesAll(): array
    {
        $model = new Classes;
        return $model->query()->get(['id as value','classes_name as label'])->toArray();
    }

}
