<?php
namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-设施-设备关联类
 */
class EnterpriseFacilityDevice extends Model
{
	protected $table = 'biz_enterprise_facility_device';

    public $timestamps = false;

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

}
