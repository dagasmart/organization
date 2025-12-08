<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-班级类
 */
class Classes extends Model
{

	protected $table = 'biz_classes';
    protected $primaryKey = 'id';

    public $timestamps = true;


    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseGradeClasses::class)->with(['grade','enterprise']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterpriseGradeClasses::class,
            'classes_id',
            'id'
        );
    }


}
