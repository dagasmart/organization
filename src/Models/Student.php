<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\Organization\Enums\Enum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * 基础-学生表模型
 */
class Student extends Model
{
    protected $table = 'biz_student';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $casts = [
        'region_info' => 'array',
        'family' => 'array',
    ];

    protected $appends = ['student_code_param', 'student_code_enc', 'id_card_enc', 'mobile_enc', 'sex_as', 'nation_as'];

    public function getIdCardAttribute($value): string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    public function getMobileAttribute($value): string
    {
        return admin_sensitive($value, 3, 4);
    }

    public function setMobileAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['mobile'] = $value;
        }
    }

    public function getStudentCodeAttribute($value): string
    {
        if (empty($value)) {
            return $value ?? '';
        }
        $first = substr($value, 0, 1);
        if ($first == 'G') {
            return admin_sensitive($value, 7, 8);
        }

        return $value;
    }

    public function setStudentCodeAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['student_code'] = $value;
        }
    }

    /**
     * 学籍号参数
     */
    public function getStudentCodeParamAttribute(): array
    {
        $data = ['type' => null, 'number' => null];

        // 增加类型和空值判断，防止传入 null 导致报错
        if (is_string($this->student_code) &&
            preg_match('/^([GJL])(.+)$/', $this->student_code, $matches)) {
            $data = [
                'type' => $matches[1] ?? null,
                'number' => $matches[2] ?? null,
            ];
        }

        return $data;
    }

    /**
     * 学籍号加密参数
     */
    public function getStudentCodeEncAttribute(): false|string
    {
        return base64_encode($this->student_code);
    }

    /**
     * 身份证号加密
     */
    public function getIdCardEncAttribute(): false|string
    {
        return base64_encode($this->id_card);
    }

    /**
     * 手机号加密
     */
    public function getMobileEncAttribute(): false|string
    {
        return base64_encode($this->mobile);
    }

    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value) ?? admin_config('admin.default_avatar');
    }

    public function setAvatarAttribute($value): void
    {
        $avatar = str_replace(Storage::url(''), '', $value);
        $this->attributes['avatar'] = Storage::url($avatar);
    }

    public function getSexAsAttribute(): ?string
    {
        $sex = array_column(Enum::sex(), 'label', 'value');

        return $sex[$this->sex ?? null] ?? null;
    }

    public function getNationAsAttribute(): ?string
    {
        $nation = array_column(Enum::nation(), 'label', 'value');

        return $nation[$this->nation ?? null] ?? null;
    }

    public function sexOption(): array
    {
        return [['value' => 1, 'label' => '男'], ['value' => 2, 'label' => '女']];
    }

    public function rel(): HasOne
    {
        return $this->hasOne(EnterpriseGradeClassesStudent::class)->with(['classes', 'grade', 'enterprise']);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classes::class, EnterpriseGradeClassesStudent::class, 'student_id', 'classes_id');
    }

    public function rel_enterprise_grade_classes_student(): HasMany
    {
        return $this->hasMany(EnterpriseGradeClassesStudent::class, 'student_id', 'id');
    }

    public function enterpriseGradeClassesStudent(): BelongsToMany
    {
        return $this->belongsToMany(
            Classes::class,
            EnterpriseGradeClassesStudent::class,
            'student_id',
            'classes_id'
        )
            // ->withTimestamps() // 自动维护 created_at 和 updated_at
            // ->wherePivot('module', admin_current_module())
            ->wherePivot('mer_id', admin_mer_id());
    }
}
