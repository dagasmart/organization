<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use DagaSmart\Organization\Enums\Enum;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-年级-班级-学生-关联模型类
 */
class EnterpriseGradeClassesStudent extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_grade_classes_student';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    // 允许批量赋值的字段
    protected $fillable = ['enterprise_id', 'grade_id', 'classes_id', 'student_id', 'state'];

    public $appends = ['state_as'];


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
