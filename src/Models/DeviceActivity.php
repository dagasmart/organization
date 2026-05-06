<?php

namespace DagaSmart\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-设备活动表
 */
class DeviceActivity extends Model
{

	protected $table = 'biz_device_activity';
    protected $primaryKey = 'id';

    public $timestamps = true;



}
