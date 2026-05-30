<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-机构-部门-职务-员工模型类
 */
class EnterpriseDepartment extends Model
{
	protected $table = 'biz_enterprise_department';

    public $timestamps = false;

    protected $appends = ['label', 'value', 'icon', 'tag'];

    /**
     * 关联机构
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(ActiveScope::class, function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->whereHas('base')
                //->where('module', admin_current_module())
                ->when($mer_id, function ($query) use ($module) {
                    $query->where('module', $module);
                })
                ->when($mer_id, function ($query) use ($mer_id) {
                    $query->where('mer_id', $mer_id);
                });
        });
    }

    /**
     * 机构
     * @return HasOne
     */
    public function enterprise(): hasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    public function getLabelAttribute()
    {
        return $this->attributes['department_name'] ?? null;
    }

    public function getValueAttribute()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getIconAttribute(): string
    {
        return 'iconfont icon-folder';
    }

    public function getTagAttribute()
    {
        return $this->attributes['remark'] ?? null;
    }



}
