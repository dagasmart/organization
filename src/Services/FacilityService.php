<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\EnterpriseFacility;
use DagaSmart\Organization\Models\Facility;
use Illuminate\Database\Eloquent\Builder;

/**
 * 基础-设施服务类
 *
 * @method Facility getModel()
 * @method Facility|Builder query()
 */
class FacilityService extends AdminService
{
    protected string $modelName = Facility::class;

    public function loadRelations($query): void
    {
        $query->whereHas('enterprise', function ($query) {
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->when($module, function ($query) use ($module) {
                $query->where('module', $module);
            })->when($mer_id, function ($query) use ($mer_id) {
                $query->where('mer_id', $mer_id);
            });
        })->with(['enterprise', 'rel']);
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->primaryKey(), 'asc');
        }
    }

    public function list(): array
    {
        $list = parent::list();
        $list['items'] = array2tree($list['items'] ?? []);

        return $list;
    }

    /**
     * 新增或修改后更新关联数据
     *
     * @param  bool  $isEdit
     */
    public function saved($model, $isEdit = false): void
    {
        admin_transaction(function () use ($model, $isEdit) {
            parent::saved($model, $isEdit);

            // 1. 防御性校验：安全获取 enterprise_id，防止未定义索引报错
            $enterpriseId = request()->input('enterprise_id');

            if (empty($enterpriseId)) {
                return; // 如果缺少必要的外键字段，直接中断，避免写入脏数据
            }

            // 2. 使用 updateOrCreate 替代 delete + insert，原子操作，性能更高
            // 注意：这里不再包裹 admin_transaction，避免 Observer 嵌套事务陷阱

            EnterpriseFacility::query()->updateOrCreate(
                ['facility_id' => $model->id], // 匹配条件：根据当前设施ID查找
                [
                    'enterprise_id' => $enterpriseId,
                    'module' => admin_current_module(),
                    'mer_id' => admin_mer_id(),
                ]
            );
        });
    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        return (new StudentService)->getEnterpriseAll();
    }

    /**
     * 递归选择项
     */
    public function options(): array
    {
        $id = request()->id ?? 0;
        $enterprise_id = request()->enterprise_id ?? 0;
        $data = $this->query()->from('biz_facility', 'a')
            ->join('biz_enterprise_facility as b', 'a.id', '=', 'b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->where('b.enterprise_id', $enterprise_id)
            ->where('b.facility_id', '<>', $id)
            ->get()
            ->toArray();

        return array2tree($data);
    }

    /**
     * 递归选择项
     */
    public function allOptions(): array
    {
        $mer_id = admin_mer_id();
        $module = admin_current_module();
        $data = $this->query()->from('biz_facility', 'a')
            ->join('biz_enterprise_facility as b', 'a.id', '=', 'b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($module, function ($query) use ($module) {
                $query->where('b.module', $module);
            })->when($mer_id, function ($query) use ($mer_id) {
                $query->where('b.mer_id', $mer_id);
            })
            ->get()
            ->toArray();

        return array2tree($data);
    }
}
