<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学校-设施关联模型类
 */
class EnterpriseFacility extends Model
{
	protected $table = 'biz_enterprise_facility';

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
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }


}
