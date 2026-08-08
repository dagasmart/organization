<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 基座模型
 */
class Model extends BaseModel
{
    // 指定使用 school 数据库连接
    protected $connection = 'school'; // 空值默认数据库

    // 关联机构
    public function base(): HasMany
    {
        return $this->hasMany(Enterprise::class, 'id', 'enterprise_id');
    }


}
