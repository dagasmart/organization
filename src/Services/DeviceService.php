<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\BizAdmin\Renderers\Json;
use DagaSmart\Organization\Models\Device;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use Illuminate\Database\Eloquent\Builder;


/**
 * 基础-设备服务类
 *
 * @method Device getModel()
 * @method Device|Builder query()
 */
class DeviceService extends AdminService
{
	protected string $modelName = Device::class;

    public function loadRelations($query): void
    {
        $query->whereHas('rel', function ($query) {
            $admin_mer_id = admin_mer_id();
            $admin_current_module = admin_current_module();
            $query->when($admin_mer_id, function (Builder $query) use ($admin_mer_id) {
                $query->where('mer_id', $admin_mer_id);
            })->when($admin_current_module, function (Builder $query) use ($admin_current_module) {
                $query->where('module', $admin_current_module);
            });
        })->with(['rel']);
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
     * 新增
     * @param $data
     * @return bool
     */
    public function store($data)
    {
        return $this->saveData($data);
    }

    /**
     * 更新
     * @param $primaryKey
     * @param $data
     * @return bool
     */
    public function update($primaryKey, $data)
    {
        return $this->saveData($data, $primaryKey);
    }
//
//
//    /**
//     * 新增或修改后更新关联数据
//     * @param $model
//     * @param bool $isEdit
//     * @return void
//     */
//    public function save(): void
//    {
//        $model->save();
//        $request = request()->all();
//        $data = [
//            'enterprise_id' => $request['enterprise_id'],
//            'facility_id' => $request['facility_id'],
//        ];
//        $model->relation()->syncWithPivotValues($model->id, $data);
//    }

    /**
     * 机构列表
     */
    public function getEnterpriseAll(): array
    {
        return (new EnterpriseService)->query()
            ->select(['id as value', 'enterprise_name as label', 'id'])
            ->get()
            ->toArray();
    }

    /**
     * 递归选择项
     * @return array
     */
    public function options(): array
    {
        $id = request()->id;
        $school_id = request()->enterprise_id;
        $data = $this->query()->from('biz_facility as a')
            ->join('biz_enterprise_facility as b','a.id','=','b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($school_id, function($query) use ($school_id) {
                $query->where('b.enterprise_id', $school_id);
            })
            ->when($id, function($query) use ($id) {
                $query->where('b.facility_id', '<>', $id);
            })
            ->get()
            ->toArray();
        return array2tree($data);
    }

    /**
     * 分(种)类型
     * @param null $key
     * @return array|string|null
     */
    public function typeOption($key = null): array|string|null
    {
        $data = [['value' => 'face', 'label' => '刷脸设备'], ['value' => 'access', 'label' => '门禁设备']];
        return $key ? $data[$key] ?? $data : null;
    }



    /**
     * 保存数据
     * 处理模型属性和角色关联的保存
     *
     * @param array $data 保存的数据
     * @param mixed|null $primaryKey 主键
     * @return bool
     */
    protected function saveData(array $data, mixed $primaryKey = null): bool
    {
        $model = $primaryKey ? $this->query()->find($primaryKey) : $this->getModel();
        $columns = $this->getTableColumns();//获取表列字段名
        foreach ($data as $k => $v) {
            if (!in_array($k, $columns)) {
                continue;
            }
            $model->setAttribute($k, $v);
        }
        if ($model->save()) {
            $extra = [
                'enterprise_id' => $data['enterprise_id'],
                'facility_id' => $data['facility_id'],
            ];
            $model->relation()->sync([$model->id => $extra]);
            return true;
        }
        return false;
    }


}
