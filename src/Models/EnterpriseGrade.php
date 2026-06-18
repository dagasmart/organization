<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;

/**
 * 基础-机构-年级关联模型类
 */
class EnterpriseGrade extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_enterprise_grade';

    public $timestamps = false;

    // 按需开启,模型表没有标记为空数组,默认开启
    protected $activeScopeFields = ['module', 'mer_id'];
}
