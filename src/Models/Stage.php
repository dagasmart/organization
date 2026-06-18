<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 基础-学段模型类
 */
class Stage extends Model
{
    protected $table = 'biz_stage';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function relation(): BelongsTo
    {
        return $this->belongsTo(Nature::class, 'type', 'type');
    }
}
