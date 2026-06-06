<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * 基础-机构模型类
 */
class Enterprise extends Model implements Auditable
{
    // 一行代码，自动增删改审计能力
    use AuditableTrait;

    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise';

    protected $primaryKey = 'id';

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    // 排除不需要审计的属性
    protected array $auditExclude = ['updated_at', 'created_at'];

    // 自定义审计事件
    protected array $auditEvents = ['created', 'updated', 'deleted'];

    protected $casts = [
        'region_info' => 'array',
        'register_time' => 'date',
    ];

    public $timestamps = false;

    // 排除字段
    public $hidden = [];


    public function getEnterpriseLogoAttribute($value): ?string
    {
        return empty($value) ? null : env('APP_URL').$value;
    }

    public function setEnterpriseLogoAttribute($value): void
    {
        $this->attributes['enterprise_logo'] = null;
        if ($value) {
            $logo = str_replace(env('APP_URL').Storage::url(''), '', $value);
            $this->attributes['enterprise_logo'] = Storage::url($logo);
        }
    }

    public function sexOption(): array
    {
        return [['value' => 1, 'label' => '男'], ['value' => 2, 'label' => '女']];
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select('id', 'enterprise_name');
    }
}
