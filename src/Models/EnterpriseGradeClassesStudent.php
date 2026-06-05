<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use DagaSmart\Organization\Enums\Enum;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-年级-班级-学生-关联模型类
 */
class EnterpriseGradeClassesStudent extends Model
{
    protected $table = 'biz_enterprise_grade_classes_student';

    // 允许批量赋值的字段
    protected $fillable = ['enterprise_id', 'grade_id', 'classes_id', 'student_id', 'state'];

    public $appends = ['state_as'];

    public $timestamps = false;

    /**
     * 关联机构
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $query->whereHas('base')
                ->when(admin_current_module(), function ($query) {
                    $query->where('module', admin_current_module());
                })
                ->when(admin_mer_id(), function ($query) {
                    $query->where('mer_id', admin_mer_id());
                });
        });
    }

    public function getStateAsAttribute(): ?string
    {
        $state = array_column(Enum::StudentState, 'label', 'value');

        return $state[$this->state ?? null] ?? null;
    }

    /**
     * 班级
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id', 'student_id')
            ->select(['id', 'student_name', 'id_card', 'mobile', 'avatar', 'sex', 'nation']);
    }

    /**
     * 班级
     */
    public function classes(): HasOne
    {
        return $this->hasOne(Classes::class, 'id', 'classes_id')->select(['id', 'classes_name']);
    }

    /**
     * 年级
     */
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class, 'id', 'grade_id')->select(['id', 'grade_name']);
    }

    /**
     * 机构
     */
    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 设备
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }
}
