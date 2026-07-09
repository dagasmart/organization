<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-设施-设备关联类
 */
class EnterpriseFacilityDevice extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_facility_device';

    public $timestamps = false;

    // ✅ 关键：告诉 Eloquent 此表没有自增 id
    public $incrementing = false;
    // ✅ 指定实际的主键字段
    protected $primaryKey = 'device_id';
    // 允许批量赋值的字段
    protected $fillable = ['enterprise_id', 'facility_id', 'device_id', 'module', 'mer_id'];

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
     * 设施
     */
    public function facility(): HasOne
    {
        return $this->hasOne(Facility::class, 'id', 'facility_id')->select(['id', 'parent_id', 'facility_name']);
    }

    /**
     * 设备
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'id', 'device_id')->select(['id', 'device_name', 'device_sn']);
    }
}
