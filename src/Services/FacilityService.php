<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Facility;
use DagaSmart\Organization\Models\EnterpriseFacility;
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
        $query->whereHas('rel', function ($query) {
            $query->where('module', admin_current_module())->where('mer_id', admin_mer_id());
        })->with(['enterprise','rel']);
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->primaryKey(), 'asc');
        }
    }

    /**
     * 新增或修改后更新关联数据
     * @param $model
     * @param bool $isEdit
     * @return void
     */
    public function saved($model, $isEdit = false): void
    {
        parent::saved($model, $isEdit);
        $request = request()->all();
        $data = [
            'enterprise_id' => $request['enterprise_id'],
            'facility_id' => $model->id,
            'module' => admin_current_module(),
            'mer_id' => admin_mer_id(),
        ];
        admin_transaction(function () use ($data) {
            if ($data['facility_id']) {
                EnterpriseFacility::query()->where('facility_id', $data['facility_id'])->delete();
            }
            EnterpriseFacility::query()->insert($data);
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
     * @return array
     */
    public function options(): array
    {
        $id = request()->id ?? 0;
        $enterprise_id = request()->enterprise_id ?? 0;
        $data = $this->query()->from('biz_facility as a')
            ->join('biz_enterprise_facility as b','a.id','=','b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->where('b.enterprise_id', $enterprise_id)
            ->where('b.facility_id', '<>', $id)
            ->get()
            ->toArray();
        return array2tree($data);
    }

    /**
     * 递归选择项
     * @return array
     */
    public function allOptions(): array
    {
        $data = $this->query()->from('biz_facility as a')
            ->join('biz_enterprise_facility as b','a.id','=','b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->where('b.module', '=', admin_current_module())
            ->where('b.mer_id', '=', admin_mer_id())
            ->get()
            ->toArray();
        return array2tree($data);
    }

}
