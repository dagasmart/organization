<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-部门-职务-员工模型类
 */
class EnterpriseDepartmentJobWorker extends Model
{
    protected $table = 'biz_enterprise_department_job_worker';

    public $timestamps = false;

    /**
     * 关联机构
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->whereHas('base')
                // ->where('module', admin_current_module())
                ->when($mer_id, function ($query) use ($module) {
                    $query->where('module', $module);
                })
                ->when($mer_id, function ($query) use ($mer_id) {
                    $query->where('mer_id', $mer_id);
                });
        });
    }

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
