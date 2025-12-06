<?php

namespace DagaSmart\School\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-老师-职务模型类
 */
class SchoolDepartmentJobTeacher extends Model
{
	protected $table = 'biz_school_department_job_teacher';

    public $timestamps = false;

    /**
     * 关联学校
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $query->whereHas('base')
                ->where('module', admin_current_module())
                ->where('mer_id', admin_mer_id());
        });
    }

    /**
     * 学校
     * @return HasOne
     */
    public function school(): hasOne
    {
        return $this->hasOne(School::class, 'id', 'school_id')->select(['id', 'school_name']);
    }

    /**
     * 部门
     * @return HasOne
     */
    public function department(): hasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id')->select(['id', 'department_name']);
    }


    /**
     * 职务
     * @return HasOne
     */
    public function job(): hasOne
    {
        return $this->hasOne(Job::class, 'id', 'job_id')->select(['id', 'job_name']);
    }


}
