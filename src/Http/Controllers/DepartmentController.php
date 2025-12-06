<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Enums\Enum;
use DagaSmart\School\Services\DepartmentService;
use DagaSmart\BizAdmin\Controllers\AdminController;
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
                amis()->TableColumn('rel.school.school_name', '学校')
                    ->searchable([
                        'name' => 'school_id',
                        'type' => 'select',
                        'multiple' => true,
                        'searchable' => true,
                        'options' => $this->service->getSchoolAll(),
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
            amis()->SelectControl('school_id', '学校')
                ->options($this->service->getSchoolAll())
                ->value('${rel.school_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('grade_id', '年级')
                ->source(admin_url('biz/school/${school_id||0}/grade'))
                ->value('${rel.grade_id}')
                ->selectMode('group')
                ->disabledOn('${!school_id}')
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
            amis()->SelectControl('school_id', '学校')
                ->options($this->service->getSchoolAll())
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('grade_id', '年级')
                ->source(admin_url('biz/school/${school_id||0}/grade'))
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
     * 学校年级班级列表
     * @param $school_id
     * @param $grade_id
     * @return array
     */
    public function SchoolGradeClasses($school_id, $grade_id): array
    {
        return $this->service->SchoolGradeClasses($school_id, $grade_id);

    }


}
