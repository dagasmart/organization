<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-部门-职务-员工模型类
 */
class EnterpriseDepartmentJobWorker extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_department_job_worker';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    /**
     * 机构
     */
    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 部门
     */
    public function department(): HasOne
    {
        return $this->hasOne(EnterpriseDepartment::class, 'id', 'department_id')->select(['id', 'department_name']);
    }

    /**
     * 职务
     */
    public function job(): HasOne
    {
        return $this->hasOne(EnterpriseDepartmentJob::class, 'id', 'job_id')->select(['id', 'job_name']);
    }
}
