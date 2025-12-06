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

    public $timestamps = true;

    /**
     * 头像
     * @param $value
     * @return string|null
     */
    public function getAvatarAttribute($value): ?string
    {
        return $value ? env('APP_URL') . $value : null;
    }

    public function setAvatarAttribute($value): void
    {
        $avatar = str_replace(env('APP_URL') . Storage::url(''), '', $value);
        $this->attributes['avatar'] = Storage::url($avatar);
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


    public function schoolThrough(): HasManyThrough
    {
        return $this->hasManyThrough(Enterprise::class, SchoolTeacher::class,
            'teacher_id',
            'id',
            'id',
            'school_id'
        )->select(admin_raw("id as value, school_name as label"));
    }

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseDepartmentJobWorker::class)->with(['job','department','school']);
    }

    public function school(): HasOne
    {
        return $this->hasOne(EnterpriseDepartmentJobWorker::class,
            'teacher_id',
            'id'
            )->select(admin_raw("
                teacher_id
                ,string_agg (DISTINCT school_id::VARCHAR, ',' ) as school_id
                ,string_agg (DISTINCT department_id::VARCHAR, ',' ) as department_id
                ,string_agg (DISTINCT job_id::VARCHAR, ',' ) as job_id
            "))
            ->groupBy('teacher_id');
    }

    public function combo(): HasMany
    {
        return $this->hasMany(EnterpriseDepartmentJobWorker::class,
            'teacher_id',
            'id'
            )
            ->withoutGlobalScope('ActiveScope')
            ->select(admin_raw("school_id,department_id,job_id,teacher_id,teacher_sn,module,mer_id"));
    }

    public function job(): HasOne
    {
        return $this->HasOne(EnterpriseDepartmentJobWorker::class,
            'teacher_id',
            'id'
            )
            ->select(admin_raw("teacher_id,string_agg(job_id::varchar, ',') job_id"))
            ->orderBy('job_id')
            ->groupBy(['teacher_id']);
    }

    public function schoolData(): Collection
    {
        return Enterprise::query()->whereNull('deleted_at')->pluck('school_name','id');
    }

    public function schoolJobs(): BelongsToMany
    {
        return $this->belongsToMany(
            Job::class,
            EnterpriseDepartmentJobWorker::class,
            'teacher_id',
            'job_id'
            )
            ->wherePivot('mer_id', admin_mer_id());
    }



}
