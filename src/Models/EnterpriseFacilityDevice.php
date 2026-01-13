<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-设施-设备关联类
 */
class EnterpriseFacilityDevice extends Model
{
	protected $table = 'biz_enterprise_facility_device';

    public $timestamps = false;

//    protected static function booted(): void
//    {
//        static::addGlobalScope(ActiveScope::class, function ($query) {
//            $query->whereHas('base');
//        });
//    }

    /**
     * 机构
     * @return HasOne
     */
    public function enterprise(): hasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 设施
     * @return HasOne
     */
    public function facility(): hasOne
    {
        return $this->hasOne(Facility::class, 'id', 'facility_id')->select(['id', 'parent_id', 'facility_name']);
    }

    /**
     * 设备
     * @return HasOne
     */
    public function device(): hasOne
    {
        return $this->hasOne(Device::class, 'id', 'device_id')->select(['id', 'device_name', 'device_sn']);
    }

}
