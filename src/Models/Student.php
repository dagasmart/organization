<?php

namespace DagaSmart\Organization\Models;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\hasOne;
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

    protected $appends = ['student_code', 'id_card_enc', 'mobile_enc'];

    public function getIdCardAttribute($value): string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && !strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    public function getMobileAttribute($value): string
    {
        return admin_sensitive($value, 3, 4);
    }

    public function setMobileAttribute($value): void
    {
        if ($value && !strpos($value, '*')) {
            $this->attributes['mobile'] = $value;
        }
    }

    public function getStudentCodeAttribute(): string
    {
        return 'G' . $this->id_card;
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

    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setAvatarAttribute($value): void
    {
        $avatar = str_replace(env('APP_URL') . Storage::url(''), '', $value);
        $this->attributes['avatar'] = Storage::url($avatar);
    }

    public function sexOption(): array
    {
        return [['value'=>1, 'label'=>'男'], ['value'=>2, 'label'=>'女']];
    }

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseGradeClassesStudent::class)->with(['classes','grade','enterprise']);
    }

    public function classes(): belongsToMany
    {
        return $this->belongsToMany(Classes::class, EnterpriseGradeClassesStudent::class, 'student_id', 'classes_id');
    }
    public function rel_enterprise_grade_classes_student(): hasMany
    {
        return $this->hasMany(EnterpriseGradeClassesStudent::class, 'student_id', 'id');
    }

}
