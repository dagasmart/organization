<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\BaseScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * 基础-家长模型类
 */
class Patriarch extends Model
{
    protected $table = 'biz_patriarch';

    protected $primaryKey = 'id';

    protected $casts = [
        'mobile' => 'int',
        'id_card' => 'string',
    ];

    protected $appends = ['id_card_enc', 'mobile_enc'];

    protected $hidden = ['child']; // 序列化时自动隐藏

    public $timestamps = true;

    protected static function booted(): void
    {
        admin_transaction(function () {
            static::deleting(function (Patriarch $patriarch) {
                $patriarch->children()
                    ->withoutGlobalScopes([BaseScope::class])
                    ->delete();
            });
        });
    }

    /**
     * 头像
     */
    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value) ?? admin_config('admin.default_avatar');
    }

    public function setAvatarAttribute($value): void
    {
        $this->attributes['avatar'] = admin_image_path($value);
    }

    /**
     * 身份证号加密
     */
    public function getIdCardEncAttribute(): false|string
    {
        return base64_encode($this->attributes['id_card']);
    }

    /**
     * 手机号加密
     */
    public function getMobileEncAttribute(): false|string
    {
        return base64_encode($this->attributes['mobile']);
    }

    /**
     * 手机号脱敏
     */
    public function getMobileAttribute($value): false|string
    {
        return admin_sensitive($value, 3, 5);
    }

    public function setMobileAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['mobile'] = $value;
        }
    }

    /**
     * 身份证号脱敏
     */
    public function getIdCardAttribute($value): false|string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    //    public function enterpriseThrough(): HasManyThrough
    //    {
    //        return $this->hasManyThrough(Enterprise::class, EnterpriseWorker::class,
    //            'worker_id',
    //            'id',
    //            'id',
    //            'enterprise_id'
    //        )->select(admin_raw("id as value, enterprise_name as label"));
    //    }

    public function child(): HasMany
    {
        return $this->hasMany(EnterprisePatriarchStudent::class, 'patriarch_id', 'id')->with('rel');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EnterprisePatriarchStudent::class, 'patriarch_id', 'id');
    }

    //    public function enterprise(): HasOne
    //    {
    //        return $this->hasOne(EnterpriseDepartmentJobWorker::class,
    //            'worker_id',
    //            'id'
    //            )->select(admin_raw("
    //                worker_id
    //                ,string_agg (DISTINCT enterprise_id::VARCHAR, ',' ) as enterprise_id
    //                ,string_agg (DISTINCT department_id::VARCHAR, ',' ) as department_id
    //                ,string_agg (DISTINCT job_id::VARCHAR, ',' ) as job_id
    //            "))
    //            ->groupBy('worker_id');
    //    }

    public function enterpriseData(): Collection
    {
        return Enterprise::query()->whereNull('deleted_at')->pluck('enterprise_name', 'id');
    }

    public function patriarchStudent(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            EnterprisePatriarchStudent::class,
            'patriarch_id',
            'student_id'
        )
            ->wherePivot('mer_id', admin_mer_id());
    }
}
