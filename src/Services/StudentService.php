<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Jobs\StudentImportJob;
use DagaSmart\Organization\Models\Classes;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
use DagaSmart\Organization\Models\Grade;
use DagaSmart\Organization\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

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
        $query->whereHas('rel', function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->when($module, function ($query) use ($module) {
                $query->where('module', $module);
            })->when($mer_id, function ($query) use ($mer_id) {
                $query->where('mer_id', $mer_id);
            });
        })->with(['rel']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('rel', function (Builder $builder) {
            $school_id = request('enterprise_id');
            $builder->when($school_id, function (Builder $builder) use (&$school_id) {
                if (! is_array($school_id)) {
                    $school_id = explode(',', $school_id);
                }
                $builder->whereIn('enterprise_id', $school_id);
            });
            $grade_id = request('grade_id');
            $builder->when($grade_id, function (Builder $builder) use (&$grade_id) {
                if (! is_array($grade_id)) {
                    $grade_id = explode(',', $grade_id);
                }
                $builder->whereIn('grade_id', $grade_id);
            });
            $classes_id = request('classes_id');
            $builder->when($classes_id, function (Builder $builder) use (&$classes_id) {
                if (! is_array($classes_id)) {
                    $classes_id = explode(',', $classes_id);
                }
                $builder->whereIn('classes_id', $classes_id);
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

    public function store($data): bool
    {
        if (! empty($data['id'])) {
            return $this->update($data['id'], $data);
        }

        return parent::store($data);
    }

    public function saving(&$data, $primaryKey = 'id'): void
    {
        // 提取地区代码
        $region_id = $data['region_id'] ?? null;
        if ($region_id) {
            if (is_array($data['region_id'])) {
                $data['region_id'] = $data['region_id']['code'];
            }
        }
        // 为0为空不存在时
        if (empty($data['region_id'])) {
            $data['region_id'] = null;
            $data['region_info'] = null;
        }
        // 手机号码
        $mobile = $data['mobile'] ?? null;
        if ($mobile && strpos($mobile, '*')) {
            unset($data['mobile']);
        }
        // 身份证号
        admin_abort_if(empty($data['id_card']), '请输入有效身份证号');
        $id_card = $data['id_card'] ?? null;
        if ($id_card) {
            if (strpos($id_card, '*')) {
                unset($data['id_card']);
            } else {
                // 身份证号校验
                identifyByIdCard($id_card);
                // 是否已存在
                $id = $data['id'] ?? null;
                $exists = $this->query()
                    ->where(['id_card' => $id_card])
                    ->when($id, function ($query) use ($id) {
                        return $query->where('id', '<>', $id);
                    })
                    ->exists();
                admin_abort_if($exists, '身份证号(${id_card})已存在，请检查');
            }
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

    /**
     * 新增或修改后更新关联数据
     *
     * @param  bool  $isEdit
     */
    public function saved($model, $isEdit = false): void
    {
        // 1. 调用父类方法（如果父类有相关逻辑）
        parent::saved($model, $isEdit);

        // 2. 防御性判断
        if (! $model) {
            return;
        }

        // 3. 使用白名单提取数据，防止恶意字段注入
        $request = request()->only([
            'enterprise_id',
            'grade_id',
            'classes_id',
            'student_id',
            'state',
        ]);

        // 4. 如果前端没有传递 classes_id，说明不需要更新关联，直接返回
        if (empty($request['classes_id'])) {
            return;
        }

        $request['module'] = admin_current_module();
        $request['mer_id'] = admin_mer_id();

        // 5. 更新关联数据
        $model->enterpriseGradeClassesStudent()->sync([
            $model->id => $request,
        ]);
    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        return Enterprise::whereHas('bind')->whereNull('deleted_at')->get(['id as value', 'enterprise_name as label'])->toArray();
    }

    /**
     * 年级列表
     */
    public function getGradeAll(): array
    {
        $data = Grade::query()->get(['id as value', 'grade_name as label', 'id', 'parent_id'])->toArray();

        return array2tree($data);
    }

    /**
     * 班级列表
     */
    public function getClassesAll(): array
    {
        return Classes::query()->get(['id as value', 'classes_name as label'])->toArray();
    }

    public function search(): LengthAwarePaginator
    {
        $input = request()->input();

        $enterprise_id = $input['enterprise_id'] ?? null;
        $grade_id = $input['grade_id'] ?? null;
        $classes_id = $input['classes_id'] ?? null;
        $student_name = $input['student_name'] ?? null;
        $id_card = $input['id_card'] ?? null;
        if ($id_card) {
            identifyByIdCard($id_card);
        }

        return EnterpriseGradeClassesStudent::query()
            ->whereHas('student', function (Builder $builder) use ($student_name, $id_card) {
                $builder->when($student_name, function (Builder $builder) use ($student_name) {
                    $builder->whereLike('student_name', '%'.$student_name.'%');
                });
                $builder->when($id_card, function (Builder $builder) use ($id_card) {
                    $builder->where('id_card', $id_card);
                });
            })
            ->where(['enterprise_id' => $enterprise_id])
            ->when(
                $grade_id,
                fn ($query) => $query->where('grade_id', $grade_id),
            )
            ->when(
                $classes_id,
                fn ($query) => $query->where('classes_id', $classes_id),
            )
            ->with(['enterprise', 'grade', 'classes', 'student'])
            ->paginate(4);
    }

    public function enterpriseStudentCheck($id_card): ?Student
    {
        $row = $this->query()
            ->where(['id_card' => $id_card])
            ->first();

        if (! $row) {
            return null; // 显式返回 null，比返回 $row 更清晰
        }

        return $row;
    }

    public function studentImport($path = ''): ?JsonResponse
    {
        if (empty($path)) {
            return null;
        }

        // 1. 生成唯一批次号（用于追踪进度）
        $batchId = Str::uuid()->toString();

        // 2. 派发到队列（立即返回，不阻塞用户）
        StudentImportJob::dispatch(
            filePath: storage_path(public_storage_path($path)),
            userId: admin_user_id(),
            batchId: $batchId
        )->onQueue('student-import');

        return response()->json([
            'message' => '导入任务已提交',
            'batch_id' => $batchId,
        ]);
    }
}
