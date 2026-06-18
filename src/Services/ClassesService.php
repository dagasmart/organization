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
        $query->with(['enterprise', 'rel']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('enterprise', function (Builder $builder) {
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
        ]);

        // 4. 如果前端没有传递 grade_id，说明不需要更新关联，直接返回
        if (empty($request['grade_id'])) {
            return;
        }

        $request['module'] = admin_current_module();
        $request['mer_id'] = admin_mer_id();

        // 5. 更新关联数据
        $model->enterpriseGradeClasses()->sync([
            $model->id => $request,
        ]);
    }

    public function deleting($ids)
    {
        if (! is_array($ids)) {
            $ids = explode(',', $ids);
        }
        admin_abort_if(! $ids, '请选择删除项');
        // 获取存在学生的班级id组
        $oids = EnterpriseGradeClassesStudent::query()
            ->whereIn('classes_id', $ids)
            ->pluck('classes_id')
            ->toArray();
        // 获取没有学生的班级id组
        $ids = array_diff($ids, $oids);
        admin_abort_if($oids && ! $ids, '当前勾选班级存在学生信息，无法删除');
        EnterpriseGradeClasses::query()->whereIn('classes_id', $ids)->delete();

        return implode(',', $ids);
    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        return (new StudentService)->getEnterpriseAll();
    }

    /**
     * 机构年级列表
     */
    public function enterpriseGradeClasses(int $enterprise_id, int $grade_id): array
    {
        return Classes::query()
            ->whereHas('enterpriseGradeClasses', function ($builder) use ($enterprise_id, $grade_id) {
                $builder
                    ->when(
                        $enterprise_id,
                        fn ($query) => $query->where('enterprise_id', $enterprise_id),
                    )
                    ->when(
                        $grade_id,
                        fn ($query) => $query->where('grade_id', $grade_id),
                    );
            })
            ->where('status', 1)
            ->orderBy('sort')
            ->get(['id as value', 'classes_name as label'])
            ->toArray();
    }
}
