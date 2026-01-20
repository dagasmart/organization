<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-家长-学生关联模型类
 */
class EnterprisePatriarchStudent extends Model
{
	protected $table = 'biz_enterprise_patriarch_student';

    public $timestamps = false;

    /**
     * 关联机构
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->whereHas('base')
                //->where('module', admin_current_module())
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
     * @return HasOne
     */
    public function enterprise(): hasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 孩子关联信息
     * @return HasOne
     */
    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'student_id')
            ->select(['enterprise_id', 'grade_id', 'classes_id', 'student_id', 'state', 'reason'])
            ->with(['enterprise', 'grade', 'classes', 'student']);
    }


}
