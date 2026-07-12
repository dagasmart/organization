<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartment;
use DagaSmart\Organization\Models\EnterpriseDepartmentJob;
use DagaSmart\Organization\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        $query->whereHas('rel', function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->when($module, function ($query) use ($module) {
                $query->where('module', $module);
            })->when($mer_id, function ($query) use ($mer_id) {
                $query->where('mer_id', $mer_id);
            });
        })->with(['rel', 'combo']);
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        $query->whereHas('enterprise', function (Builder $builder) {
            $enterprise_id = request('enterprise_id');
            $builder->when($enterprise_id, function (Builder $builder) use (&$enterprise_id) {
                if (! is_array($enterprise_id)) {
                    $enterprise_id = explode(',', $enterprise_id);
                }
                $builder->whereIn('enterprise_id', $enterprise_id);
            });
            $department_id = request('department_id');
            $builder->when($department_id, function (Builder $builder) use (&$department_id) {
                if (! is_array($department_id)) {
                    $department_id = explode(',', $department_id);
                }
                $builder->whereIn('department_id', $department_id);
            });
            $job_id = request('job_id');
            $builder->when($job_id, function (Builder $builder) use (&$job_id) {
                if (! is_array($job_id)) {
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

    public function list(): array
    {
        $list = parent::list();
        if ($list['items']) {
            foreach ($list['items'] as &$item) {
                // $enterprise_department_job_array = [];
                if (! empty($item['rel'])) {
                    foreach ($item['rel'] as $k => &$rel) {
                        $key = $rel['enterprise_id'].'_'.$rel['department_id'].'_'.$rel['job_id'];

                        $tmp = [];
                        $tmp[] = $rel['enterprise']['enterprise_name'] ?? null;
                        $tmp[] = $rel['department']['department_name'] ?? null;
                        $tmp[] = $rel['job']['job_name'] ?? null;
                        $implode = implode(' / ', array_filter($tmp));

                        $rel['value'] = $key;
                        $rel['label'] = $implode;

                        // $enterprise_department_job_array[$k]['value'] = $key;
                        // $enterprise_department_job_array[$k]['label'] = $implode;
                    }
                }
                // $item['enterprise_department_job'] = $enterprise_department_job_array;
            }
        }

        return $list;
    }

    public function store($data): bool
    {
        $id = $data['id'] ?? null;
        if ($id) {
            $data = array_intersect_key($data, array_flip(['id', 'id_card', 'combo'])) ?? null;
            admin_abort_if(! $data, '职务信息不能为空');

            return $this->update($id, $data);
        } else {
            unset($data['id']);

            return parent::store($data);
        }
    }

    public function saving(&$data, $primaryKey = ''): void
    {
        if (is_repeat($data['combo'])) {
            admin_abort('机构信息12：部门或职务选项有重叠，请修改或删除');
        }
        // 地区代码
        $region = $data['region_id'] ?? null;
        if ($region) {
            if (is_array($region)) {
                $data['region_id'] = $region['code'] ?? null;
            }
            // admin_region_code($data['region_id']); // 地区code更新缓存
        }
        // 手机号码
        $mobile = $data['mobile'] ?? null;
        if ($mobile && strpos($mobile, '*')) {
            unset($data['mobile']);
        }

        admin_abort_if(empty($data['id_card']), '请输入有效身份证号');
        // 身份证号
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

    public function saved($model, $isEdit = false): void
    {

        $combo = $this->request->combo ?? null;

        // 防御性判断
        if (! $model || empty($combo)) {
            return;
        }

        $data = [];
        $module = admin_current_module();
        $mer_id = admin_mer_id();

        // 1. 仅做数据组装，绝对不要在循环中执行数据库操作
        foreach ($combo as $item) {
            $jobs = explode(',', $item['job_id']);

            foreach ($jobs as $jobId) {
                $data[] = [
                    'enterprise_id' => $item['enterprise_id'],
                    'department_id' => $item['department_id'],
                    'job_id' => $jobId,
                    'worker_id' => $model->id,
                    'worker_no' => $item['enterprise_id'].$model->id,
                    'state' => $item['state'] ?? 0,
                    'module' => $item['module'] ?? $module,
                    'mer_id' => $item['mer_id'] ?? $mer_id,
                ];
            }
        }

        // 2. 使用事务保证数据一致性
        admin_transaction(function () use ($model, $data) {
            // 3. 直接调用 sync，Laravel 会自动比对差异并执行安全的增删操作
            // 注意：如果中间表没有唯一索引，sync 可能会报重复插入错误，需确保表结构正确
            $model->enterpriseJobs()->sync($data);
        });

    }

    /**
     * 机构列表
     */
    public function enterpriseData(): Collection
    {
        return $this->getModel()->enterpriseData();
    }

    public function EnterpriseWorkerCheck($id_card)
    {
        return $this->query()
            ->with(['enterprise', 'rel', 'combo'])
            ->where(['id_card' => $id_card])
            ->first();
    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        $model = new Enterprise;

        return $model->query()
            ->whereHas('bind')
            ->whereNull('deleted_at')
            ->get(['id as value', 'enterprise_name as label'])
            ->toArray();
    }

    public function departmentData(): array
    {
        $enterprise_id = request()->enterprise_id ?? 0;
        $model = new EnterpriseDepartment;
        $data = $model->query()
            ->where('enterprise_id', $enterprise_id)
//            ->when($enterprise_id, function ($builder) use ($enterprise_id) {
//                $builder->where('enterprise_id', $enterprise_id);
//            })
            ->get()
            ?->toArray();

        return array2tree($data);
    }

    public function jobData(): array
    {
        $enterprise_id = request()->enterprise_id ?? 0;
        $model = new EnterpriseDepartmentJob;
        $data = $model->query()
            ->where('enterprise_id', $enterprise_id)
            ->get()
            ?->toArray();

        return array2tree($data);
    }

    public function departmentJobData($id = null): ?Collection
    {
        $enterprise_id = request()->enterprise_id ?? $id ?? 0;
        $department_id = request()->department_id ?? 0;
        $model = new EnterpriseDepartmentJob;

        $record = $model->query()
            ->where('enterprise_id', $enterprise_id)
            ->when($department_id, function ($builder) use ($department_id) {
                $builder->where('department_id', $department_id);
            })
            ->get();

        return $record?->load('children.children.children.children.children');

    }
}
