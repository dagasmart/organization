<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\Organization\Services\DepartmentService;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;

/**
 * 基础-部门类
 *
 * @property DepartmentService $service
 */
class DepartmentController extends AdminController
{
	protected string $serviceName = DepartmentService::class;

	public function list(): Page
    {
		$crud = $this->baseCRUD()
			->filterTogglable(false)
			->headerToolbar([
				$this->createButton('dialog',250),
				...$this->baseHeaderToolBar()
			])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed','left'),
                amis()->TableColumn('rel.enterprise.enterprise_name', '机构单位')
                    ->searchable([
                        'name' => 'enterprise_id',
                        'type' => 'select',
                        'multiple' => true,
                        'searchable' => true,
                        'options' => $this->service->getEnterpriseAll(),
                    ])
                    ->width(200),
                amis()->TableColumn('rel.grade.grade_name', '年级')->width(100),
                amis()->TableColumn('classes_name','班级')->sortable(),
                amis()->TableColumn('status', '状态')
                    ->set('type','status')
                    ->searchable(),
                amis()->TableColumn('updated_at', '更新时间')->type('datetime')->width(150),
                $this->rowActions('dialog',250)
                    ->set('align','center')
                    ->set('fixed','right')
                    ->set('width',150)
            ]);

		return $this->baseList($crud);
	}

	public function form($isEdit = false): Form
    {
		return $this->baseForm()->body([
            amis()->StaticExactControl('id','ID')->visibleOn('${id}'),
            amis()->SelectControl('enterprise_id', '机构单位')
                ->options($this->service->getEnterpriseAll())
                ->value('${rel.enterprise_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('grade_id', '年级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                ->value('${rel.grade_id}')
                ->selectMode('group')
                ->disabledOn('${!enterprise_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TextControl('classes_name','班级')
                ->disabledOn('${!grade_id}')
                ->maxLength(50)
                ->clearable()
                ->required(),
            amis()->NumberControl('sort','排序')->size('xs'),
            amis()->SwitchControl('status','状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true),
		]);
	}

	public function detail(): Form
    {
		return $this->baseDetail()->body([
            amis()->StaticExactControl('id','ID')->visibleOn('${id}'),
            amis()->SelectControl('enterprise_id', '机构单位')
                ->options($this->service->getEnterpriseAll())
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('grade_id', '年级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                ->selectMode('group')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TextControl('classes_name','班级')
                ->maxLength(50)
                ->clearable()
                ->required(),
            amis()->NumberControl('sort','排序')->size('xs'),
            amis()->SwitchControl('status','状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true),
		])->static();
	}

    /**
     * 机构年级班级列表
     * @param $school_id
     * @param $grade_id
     * @return array
     */
    public function enterpriseGradeClasses($school_id, $grade_id): array
    {
        return $this->service->enterpriseGradeClasses($school_id, $grade_id);

    }


}
