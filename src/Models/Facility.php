<?php

namespace DagaSmart\School\Models;

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
        return $this->hasOne(SchoolFacility::class)->with(['school']);
    }

    public function school(): HasOne
    {
        return $this->hasOne(SchoolFacility::class,
            'facility_id',
            'id'
        )->with(['school']);
    }

    public function getLevelNameAttribute()
    {
        return FacilityLevel::query()->where(['id' => $this->id])->value('level_name');
    }

}
