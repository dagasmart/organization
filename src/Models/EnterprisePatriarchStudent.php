<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-家长-学生关联模型类
 */
class EnterprisePatriarchStudent extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_patriarch_student';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];
    protected $hidden = ['module', 'mer_id'];

    /**
     * 机构
     */
    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 家长
     */
    public function patriarch(): HasOne
    {
        return $this->hasOne(Patriarch::class, 'id', 'patriarch_id')->select(['id', 'patriarch_name', 'id_card', 'mobile']);
    }

    /**
     * 学生
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id', 'student_id')->select(['id', 'student_name', 'id_card', 'mobile']);
    }

    /**
     * 孩子关联信息
     */
    public function rel(): HasOne
    {
        return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'student_id')
            ->select(['enterprise_id', 'grade_id', 'classes_id', 'student_id', 'state', 'reason'])
            ->with(['enterprise', 'grade', 'classes', 'student']);
    }
}
