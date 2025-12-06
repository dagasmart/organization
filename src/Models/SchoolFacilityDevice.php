<?php

namespace DagaSmart\School\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学校-设施-设备关联类
 */
class SchoolFacilityDevice extends Model
{
	protected $table = 'biz_school_facility_device';

    public $timestamps = false;

//    protected static function booted(): void
//    {
//        static::addGlobalScope(ActiveScope::class, function ($query) {
//            $query->whereHas('base');
//        });
//    }

    /**
     * 学校
     * @return HasOne
     */
    public function school(): hasOne
    {
        return $this->hasOne(School::class, 'id', 'school_id')->select(['id', 'school_name']);
    }

    /**
     * 设施
     * @return HasOne
     */
    public function facility(): hasOne
    {
        return $this->hasOne(Facility::class, 'id', 'facility_id')->select(['id', 'parent_id', 'facility_name']);
    }

}
