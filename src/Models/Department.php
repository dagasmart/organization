<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-部门类
 */
class Department extends Model
{

	protected $table = 'biz_department';
    protected $primaryKey = 'id';

    public $timestamps = true;


    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseGradeClasses::class)->with(['grade','school']);
    }

}
