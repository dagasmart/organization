<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;


/**
 * 基础-老师模型类
 */
class Worker extends Model
{
	protected $table = 'biz_worker';
    protected $primaryKey = 'id';

    protected $casts = [
        'region_info' => 'array',
        'family' => 'array',
    ];

    protected $appends = ['id_card_enc', 'mobile_enc'];

    public $timestamps = true;

    /**
     * 头像
     * @param $value
     * @return string|null
     */
    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setAvatarAttribute($value): void
    {
        $avatar = str_replace(env('APP_URL') . Storage::url(''), '', $value);
        $this->attributes['avatar'] = $value ? Storage::url($avatar) : null;
    }

    /**
     * 身份证号加密
     * @return false|string
     */
    public function getIdCardEncAttribute(): false|string
    {
        return base64_encode($this->attributes['id_card']);
    }

    /**
     * 手机号加密
     * @return false|string
     */
    public function getMobileEncAttribute(): false|string
    {
        return base64_encode($this->mobile);
    }

    /**
     * 手机号脱敏
     * @param $value
     * @return false|string
     */
    public function getMobileAttribute($value): false|string
    {
        return admin_sensitive($value, 3,5);
    }

    public function setMobileAttribute($value): void
    {
        if ($value && !strpos($value, '*')) {
            $this->attributes['mobile'] = $value;
        }
    }

    /**
     * 身份证号脱敏
     * @param $value
     * @return false|string
     */
    public function getIdCardAttribute($value): false|string
    {
        return admin_sensitive($value, 6,8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && !strpos($value, '*')) {
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

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseDepartmentJobWorker::class)->with(['job','department','enterprise']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterpriseDepartmentJobWorker::class,
            'worker_id',
            'id'
            )->select(admin_raw("
                worker_id
                ,string_agg (DISTINCT enterprise_id::VARCHAR, ',' ) as enterprise_id
                ,string_agg (DISTINCT department_id::VARCHAR, ',' ) as department_id
                ,string_agg (DISTINCT job_id::VARCHAR, ',' ) as job_id
            "))
            ->groupBy('worker_id');
    }

    public function combo(): HasMany
    {
        return $this->hasMany(EnterpriseDepartmentJobWorker::class,
            'worker_id',
            'id'
            )
            ->withoutGlobalScope('ActiveScope')
            ->select(admin_raw("enterprise_id,department_id,job_id,worker_id,worker_sn,module,mer_id"));
    }

    public function job(): HasOne
    {
        return $this->HasOne(EnterpriseDepartmentJobWorker::class,
            'worker_id',
            'id'
            )
            ->select(admin_raw("worker_id,string_agg(job_id::varchar, ',') job_id"))
            ->orderBy('job_id')
            ->groupBy(['worker_id']);
    }

    public function enterpriseData(): Collection
    {
        return Enterprise::query()->whereNull('deleted_at')->pluck('enterprise_name','id');
    }

    public function enterpriseJobs(): BelongsToMany
    {
        return $this->belongsToMany(
            Job::class,
            EnterpriseDepartmentJobWorker::class,
            'worker_id',
            'job_id'
            )
            ->wherePivot('mer_id', admin_mer_id());
    }



}
