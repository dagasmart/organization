<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 基础-机构-商户关联模型类
 */
class EnterpriseBind extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_bind';

    public $timestamps = false;

    // ✅ 关键：告诉 Eloquent 此表没有自增 id
    public $incrementing = false;
    // ✅ 指定实际的主键字段
    protected $primaryKey = 'enterprise_id';
    // 允许批量赋值的字段
    protected $fillable = ['enterprise_id', 'module', 'mer_id'];

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    /**
     * 机构
     */
    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_id', 'id');
    }
}
