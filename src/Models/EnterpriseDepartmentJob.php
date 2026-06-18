<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-部门-职务-员工模型类
 */
class EnterpriseDepartmentJob extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_department_job';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    protected $appends = ['label', 'value', 'icon', 'tag'];

    /**
     * 机构
     */
    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    public function children(): HasMany
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }

    public function getLabelAttribute()
    {
        return $this->attributes['job_name'] ?? null;
    }

    public function getValueAttribute()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getIconAttribute(): string
    {
        return 'iconfont icon-user';
    }

    public function getTagAttribute()
    {
        return $this->attributes['remark'] ?? null;
    }
}
