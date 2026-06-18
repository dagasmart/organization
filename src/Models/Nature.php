<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 基础-学段模型类
 */
class Nature extends Model
{
    protected $table = 'biz_nature';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function relation(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'type', 'type');
    }
}
