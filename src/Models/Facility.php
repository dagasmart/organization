<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-设施类
 */
class Facility extends Model
{

	protected $table = 'biz_facility';
    protected $primaryKey = 'id';

    protected $appends = ['level_name'];

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseFacility::class)->with(['enterprise']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterpriseFacility::class,
            'facility_id',
            'id'
        )->with(['enterprise']);
    }

    public function getLevelNameAttribute()
    {
        return FacilityLevel::query()->where(['id' => $this->id])->value('level_name');
    }

}
