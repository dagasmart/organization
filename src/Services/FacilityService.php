<?php

namespace DagaSmart\School\Services;

use DagaSmart\School\Models\Facility;
use DagaSmart\School\Models\SchoolFacility;
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
        $query->with(['school','rel']);
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
            'school_id' => $request['school_id'],
            'facility_id' => $model->id,
        ];
        admin_transaction(function () use ($data) {
            if ($data['facility_id']) {
                SchoolFacility::query()->where('facility_id', $data['facility_id'])->delete();
            }
            SchoolFacility::query()->insert($data);
        });
    }

    /**
     * 学校列表
     */
    public function getSchoolAll(): array
    {
        return (new StudentService)->getSchoolAll();
    }

    /**
     * 递归选择项
     * @return array
     */
    public function options(): array
    {
        $id = request()->id;
        $school_id = request()->school_id;
        $data = $this->query()->from('biz_facility as a')
            ->join('biz_school_facility as b','a.id','=','b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($school_id, function($query) use ($school_id) {
                $query->where('b.school_id', $school_id);
            })
            ->when($id, function($query) use ($id) {
                $query->where('b.facility_id', '<>', $id);
            })
            ->get()
            ->toArray();
        return array2tree($data);
    }

}
