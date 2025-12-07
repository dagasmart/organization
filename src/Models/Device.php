<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学生表
 */
class Device extends Model
{

	protected $table = 'biz_device';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class,'device_id','id')->with(['school','facility']);
    }

    public function school(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class,
            'device_id',
            'id'
            )->with(['school']);
    }

    public function relation(): belongsToMany
    {
        return $this->belongsToMany(
            static::class,
            EnterpriseFacilityDevice::class,
            'device_id'
        );
    }

}
