<?php

namespace DagaSmart\School\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学校-年级-班级-学生-关联模型类
 */
class SchoolGradeClassesStudent extends Model
{
	protected $table = 'biz_school_grade_classes_student';

    // 允许批量赋值的字段
    protected $fillable = ['school_id','grade_id','classes_id','student_id'];

    public $timestamps = false;

    /**
     * 关联学校
     * @return void
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

    /**
     * 班级
     * @return HasOne
     */
    public function classes(): hasOne
    {
        return $this->hasOne(Classes::class, 'id', 'classes_id')->select(['id', 'classes_name']);
    }

    /**
     * 年级
     * @return HasOne
     */
    public function grade(): hasOne
    {
        return $this->hasOne(Grade::class, 'id', 'grade_id')->select(['id', 'grade_name']);
    }

    /**
     * 学校
     * @return HasOne
     */
    public function school(): hasOne
    {
        return $this->hasOne(School::class, 'id', 'school_id')->select(['id', 'school_name']);
    }

}
