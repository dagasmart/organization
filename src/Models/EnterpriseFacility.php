<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-设施关联模型类
 */
class EnterpriseFacility extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_facility';

    protected $primaryKey = 'facility_id';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    /**
     * 允许被批量赋值的属性（白名单）
     */
    protected $fillable = [
        'enterprise_id',
        'facility_id',
        'module',
        'mer_id',
    ];

    /**
     * 机构
     */
    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }
}
