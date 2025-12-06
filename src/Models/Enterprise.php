<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\hasOne;
use Illuminate\Support\Facades\Storage;

/**
 * 基础-机构模型类
 */
class Enterprise extends Model
{

	protected $table = 'biz_enterprise';
    protected $primaryKey = 'id';

    protected $casts = [
        'region_info' => 'array',
        'register_time' => 'date',
    ];

    public $timestamps = false;

    public $hidden = []; //排除字段


    public function getSchoolLogoAttribute($value): ?string
    {
        return empty($value) ? null : env('APP_URL') . $value;
    }

    public function setSchoolLogoAttribute($value): void
    {
        $this->attributes['enterprise_logo'] = null;
        if ($value) {
            $logo = str_replace(env('APP_URL') . Storage::url(''), '', $value);
            $this->attributes['enterprise_logo'] = Storage::url($logo);
        }
    }

    public function sexOption(): array
    {
        return [['value'=>1, 'label'=>'男'], ['value'=>2, 'label'=>'女']];
    }

    public function school(): hasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select('id','enterprise_name');
    }

}
