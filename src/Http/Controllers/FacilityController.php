<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Services\FacilityService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;


/**
 * 基础-设施类
 *
 * @property FacilityService $service
 */
class FacilityController extends AdminController
{
	protected string $serviceName = FacilityService::class;

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
                amis()->TableColumn('id', 'ID')
                    ->sortable()
                    ->set('fixed','left'),
                amis()->TableColumn('rel.school.school_name', '学校')
                    ->searchable([
                        'name' => 'school_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getSchoolAll(),
                    ])
                    ->width(200),
                amis()->TableColumn('facility_name', '设施名称')->width(200),
                amis()->TableColumn('parent_id', '主体')
                    ->set('type', 'tree-select')
                    ->set('options', $this->service->options())
                    ->set('static', true)
                    ->width(150),
                amis()->TableColumn('facility_code','设施编码')->width(150),
                amis()->TableColumn('state', '状态')
                    ->set('type','status'),
                amis()->TableColumn('sort','排序'),
                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->sortable()
                    ->width(150),
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
            amis()->SelectControl('school_id', '学校')
                ->options($this->service->getSchoolAll())
                ->value('${rel.school.id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('parent_id', '选择主体')
                ->source(admin_url('biz/school/${school_id||0}/facility/${id||0}/options'))
                ->options($this->service->options())
                ->disabledOn('${!school_id}')
                ->searchable()
                ->clearable(),
            amis()->TextControl('facility_name', '设施名称')
                ->clearable()
                ->required(),
            amis()->TextControl('facility_code', '设施编码')
                ->clearable(),
            amis()->TextareaControl('facility_desc', '设施描述')
                ->clearable(),
            amis()->NumberControl('sort', '排序')
                ->min(0)
                ->max(100)
                ->size('xs')
                ->value(10),
            amis()->SwitchControl('state','状态')
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
                ->value('${rel.school.id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('parent_id', '选择主体')
                ->source(admin_url('biz/school/${school_id||0}/facility/${id||0}/options'))
                ->options($this->service->options())
                ->disabledOn('${!school_id}')
                ->searchable()
                ->clearable(),
            amis()->TextControl('facility_name', '设施名称')
                ->clearable()
                ->required(),
            amis()->TextControl('facility_code', '设施编码')
                ->clearable(),
            amis()->TextareaControl('facility_desc', '设施描述')
                ->clearable(),
            amis()->NumberControl('sort', '排序')
                ->min(0)
                ->max(100)
                ->size('xs')
                ->value(10),
            amis()->SwitchControl('state','状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true)
                ->disabled()
                ->static(false),
		])->static();
	}

    public function options(): array
    {
        return $this->service->options();
    }


}
