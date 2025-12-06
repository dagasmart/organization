<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Department;
use DagaSmart\Organization\Models\Job;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\Worker;
use Illuminate\Database\Eloquent\Builder;


/**
 * 基础-老师服务类
 *
 * @method Worker getModel()
 * @method Worker|Builder query()
 */
class WorkerService extends AdminService
{
	protected string $modelName = Worker::class;

    public function loadRelations($query): void
    {
        $query->with(['school','rel','combo']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('school', function (Builder $builder) {
            $school_id = request('school_id');
            $builder->when($school_id, function (Builder $builder) use (&$school_id) {
                if (!is_array($school_id)) {
                    $school_id = explode(',', $school_id);
                }
                $builder->whereIn('school_id', $school_id);
            });
            $department_id = request('department_id');
            $builder->when($department_id, function (Builder $builder) use (&$department_id) {
                if (!is_array($department_id)) {
                    $department_id = explode(',', $department_id);
                }
                $builder->whereIn('department_id', $department_id);
            });
            $job_id = request('job_id');
            $builder->when($job_id, function (Builder $builder) use (&$job_id) {
                if (!is_array($job_id)) {
                    $job_id = explode(',', $job_id);
                }
                $builder->whereIn('job_id', $job_id);
            });
        });
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->getModel()->getKeyName(), 'asc');
        }
    }

    public function store($data): bool
    {
        $id = $data['id'] ?? null;
        if ($id) {
            return $this->update($id, $data);
        } else {
            return parent::store($data);
        }
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        if (is_repeat($data['combo'])) {
            admin_abort('机构信息12：部门或职务选项有重叠，请修改或删除');
        }
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

    public function saved($model, $isEdit = false): void
    {
        $combo = $this->request->combo ?? null;
        if ($model && $combo) {
            $current = [];
            array_walk($combo, function ($item) use ($model, &$current) {
                $jobs = explode(',', $item['job_id']);
                array_walk($jobs, function ($value) use ($model, $item, &$current) {
                    $school_id = $item['school_id'];
                    $department_id = $item['department_id'];
                    $teacher_id = $model->id;
                    $module = $item['module'] ?? admin_current_module();
                    $mer_id = $item['mer_id'] ?? admin_mer_id();
                    $row = [];
                    $row['school_id'] = $school_id;
                    $row['department_id'] = $department_id;
                    $row['job_id'] = $value;
                    $row['teacher_id'] = $teacher_id;
                    $row['teacher_sn'] = $school_id . $teacher_id;
                    $row['module'] = $module;
                    $row['mer_id'] = $mer_id;
                    $current[] = $row;
                    EnterpriseDepartmentJobWorker::query()->where($row)->forceDelete();
                });
            });
            $model->schoolJobs()->sync($current);
        }
    }

    /**
     * 机构列表
     */
    public function schoolData(): \Illuminate\Support\Collection
    {
        return $this->getModel()->schoolData();
    }

    public function SchoolTeacherCheck($id_card)
    {
        return $this->query()
            ->where(['id_card' => $id_card])
            ->first();
    }

    /**
     * 机构列表
     * @return array
     */
    public function getEnterpriseAll(): array
    {
        $model = new Enterprise;
        return $model->query()->whereNull('deleted_at')->get(['id as value','enterprise_name as label'])->toArray();
    }

    /**
     * 部门列表
     * @return array
     */
    public function getDepartmentAll(): array
    {
        $model = new Department;
        $res = $model->query()
            ->select(admin_raw('*, department_name as label, id as value'))
            ->orderBy('sort')
            ->get()
            ->toArray();
        return array2tree($res, 0);
    }

    /**
     * 职务列表
     */
    public function getJobAll(): array
    {
        //Job::initialize();
        $list = Job::query()
            ->select(admin_raw('*, job_name as label, id as value'))
            ->orderBy('sort')
            ->get()
            ->toArray();
        return array2tree($list, 0);
    }

}
