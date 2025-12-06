<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Models\BaseModel;
use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *基座模型
 */
class Model extends BaseModel
{

    protected $connection = 'school'; // 使用school数据库连接

    public function __construct()
    {
        if(!isset($this->connection)){
            $this->setConnection($this->connection);
        }
        parent::__construct();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveScope('school'));
        parent::booted();
    }

    //关联学校
    public function base(): hasMany
    {
        return $this->hasMany(Enterprise::class, 'id', 'school_id');
    }

}
