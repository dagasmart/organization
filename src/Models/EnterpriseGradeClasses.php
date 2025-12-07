<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-年级-班级关联模型类
 */
class EnterpriseGradeClasses extends Model
{
	protected $table = 'biz_enterprise_grade_classes';

    public $timestamps = false;


    /**
     * 关联机构
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $query->whereHas('base')
                ->when(admin_current_module(), function ($query) {
                    $query->where('module', admin_current_module());
                })
                ->when(admin_mer_id(), function ($query) {
                    $query->where('mer_id', admin_mer_id());
                });
        });
    }


    /**
     * 年级
     * @return HasOne
     */
    public function grade(): hasOne
    {
        return $this->hasOne(Grade::class, 'id', 'grade_id')->select(['id', 'grade_name']);
    }

    /**
     * 机构
     * @return HasOne
     */
    public function enterprise(): hasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }


}
