<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Device;
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
            $mer_id = admin_mer_id();
            $module = admin_current_module();
            $query->when($mer_id, function (Builder $query) use ($mer_id) {
                $query->where('mer_id', $mer_id);
            })->when($module, function (Builder $query) use ($module) {
                $query->where('module', $module);
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
     */
    public function store($data): bool
    {
        return $this->saveData($data);
    }

    /**
     * 更新
     */
    public function update($primaryKey, $data): bool
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
        $service = new EnterpriseService;
        return $service->getEnterpriseAll();
    }

    /**
     * 递归选择项
     */
    public function options(): array
    {
        $id = request()->id;
        $enterprise_id = request()->enterprise_id;
        $data = $this->query()->from('biz_facility', 'a')
            ->join('biz_enterprise_facility as b', 'a.id', '=', 'b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->where('b.enterprise_id', $enterprise_id);
            })
            ->when($id, function ($query) use ($id) {
                $query->where('b.facility_id', '<>', $id);
            })
            ->get()
            ->toArray();

        return array2tree($data);
    }

    /**
     * 设备选择项
     */
    public function deviceOptions(): array
    {
        $id = request()->id;
        $enterprise_id = request()->enterprise_id; // 机构单位
        $facility_id = request()->facility_id;  // 主体设施
        $device_type = request()->device_type; // 设备类型
        $device_brand = request()->device_brand; // 设备品牌

        return $this->query()->from('biz_device', 'a')
            ->join('biz_enterprise_facility_device as b', 'a.id', '=', 'b.device_id')
            ->select(['a.id as value', admin_raw("concat(device_name, ' ', device_sn) as label"), 'a.device_name as name'])
            ->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->where('b.enterprise_id', $enterprise_id);
            })
            ->when($facility_id, function ($query) use ($facility_id) {
                $query->where('b.facility_id', $facility_id);
            })
            ->when($device_type, function ($query) use ($device_type) {
                $query->where('a.device_type', $device_type);
            })
            ->when($device_brand, function ($query) use ($device_brand) {
                $query->where('a.device_brand', $device_brand);
            })
            ->distinct()
            ->get()
            ->toArray();
    }

    /**
     * 分(种)类型
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
     * @param  array  $data  保存的数据
     * @param  mixed|null  $primaryKey  主键
     */
    protected function saveData(array $data, mixed $primaryKey = null): bool
    {
        // 1.安全获取模型
        $model = $primaryKey ? $this->query()->findOrFail($primaryKey) : $this->getModel();

        // 2.安全过滤并填充字段
        array_walk($data, function ($value, $key) use ($model) {
            $columns = $this->getTableColumns(); // 获取表列字段名
            if (in_array($key, $columns)) {
                $model->{$key} = $value;
            }
        });

        // 3.使用事务保证数据一致性
        return admin_transaction(function () use ($model, $data) {
            if ($model->save()) {
                // 4.关联同步逻辑
                if (! empty($data['enterprise_id']) && ! empty($data['facility_id'])) {
                    $extra = [
                        'enterprise_id' => $data['enterprise_id'],
                        'facility_id' => $data['facility_id'],
                        'module' => admin_current_module(),
                        'mer_id' => admin_mer_id(),
                    ];
                    // 5.使用 getKey() 替代硬编码的 $model->id
                    $model->relation()->sync([$model->getKey() => $extra]);
                }

                return true;
            }

            return false;
        });
    }
}
