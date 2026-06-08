<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-设施关联模型类
 */
class EnterpriseFacility extends Model
{
    protected $table = 'biz_enterprise_facility';

    protected $primaryKey = 'facility_id';

    public $timestamps = false;

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
