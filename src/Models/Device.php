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

    public function getImagesAttribute($value): ?array
    {
        return admin_images_url($value);
    }

    public function setImagesAttribute($value): void
    {
        $this->attributes['images'] = admin_images_path($value);
    }

    public function rel(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class, 'device_id', 'id')->with(['enterprise', 'facility']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class,
            'device_id',
            'id'
        )->with(['enterprise']);
    }

    public function relation(): BelongsToMany
    {
        return $this->belongsToMany(
            static::class,
            EnterpriseFacilityDevice::class,
            'device_id'
        );
    }
}
