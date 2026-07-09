<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构模型类
 */
class Enterprise extends Model
{
    protected $table = 'biz_enterprise';

    protected $primaryKey = 'id';

    protected $casts = [
        'region_info' => 'array',
        // 'register_time' => 'date',
    ];

    public $timestamps = false;

    // 排除字段
    public $hidden = [];

    public $appends = ['is_creator'];

    public function getEnterpriseLogoAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setEnterpriseLogoAttribute($value): void
    {
        $this->attributes['enterprise_logo'] = admin_image_path($value);
    }

    public function getIsCreatorAttribute(): bool
    {
        return empty(admin_mer_id()) || $this->creator_id === admin_mer_id();
    }

    public function sexOption(): array
    {
        return [['value' => 1, 'label' => '男'], ['value' => 2, 'label' => '女']];
    }

    public function bind(): HasMany
    {
        return $this->hasMany(EnterpriseBind::class, 'enterprise_id', 'id');
    }

    public function nature(): HasOne
    {
        return $this->hasOne(Nature::class, 'id', 'nature_id');
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select('id', 'enterprise_name');
    }

    public function enterpriseGrade(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class, EnterpriseGrade::class, 'enterprise_id', 'grade_id');
    }

    public function enterpriseBind(): HasOne
    {
        return $this->hasOne(EnterpriseBind::class, 'enterprise_id', 'id');
    }
}
