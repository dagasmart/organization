<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Enums\Enum;
use DagaSmart\School\Services\DeviceService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;


/**
 * 基础-设备类
 *
 * @property DeviceService $service
 */
class DeviceController extends AdminController
{
	protected string $serviceName = DeviceService::class;

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
                amis()->TableColumn('device_type', '设备类型')
                    ->searchable([
                        'name' => 'device_type',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => Enum::DeviceType,
                    ])
                    ->set('type', 'select')
                    ->set('options', Enum::DeviceType)
                    ->set('static', true),
                amis()->TableColumn('device_name', '设备名称')->width(200),
                amis()->TableColumn('rel.facility.level_name', '设施主体')
                    ->searchable([
                        'name' => 'facility_id',
                        'type' => 'tree-select',
                        'multiple' => true,
                        'options' => $this->service->options(),
                    ])
                    ->width(200),
                amis()->TableColumn('device_sn','设备编号')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                    ])
                    ->width(150),
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
                ->value('${rel.school_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('facility_id', '设施主体')
                ->source(admin_url('biz/school/${school_id||0}/facility/options'))
                ->options($this->service->options())
                ->value('${rel.facility.id}')
                ->disabledOn('${!school_id}')
                ->onlyLeaf()
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('device_type','设备类型')->options(Enum::DeviceType),
            amis()->TextControl('device_name', '设备名称')
                ->clearable()
                ->required(),
            amis()->TextControl('device_sn', '设备编号')
                ->clearable()
                ->required(),
            amis()->TextareaControl('device_desc', '设备描述')
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
                ->value('${rel.school_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TextControl('facility_id', '设施主体')
                ->value('${rel.facility.level_name}')
                ->disabledOn('${!school_id}')
                ->clearable()
                ->required(),
            amis()->SelectControl('device_type','设备类型')->options(Enum::DeviceType),
            amis()->TextControl('device_name', '设备名称')
                ->clearable()
                ->required(),
            amis()->TextControl('device_sn', '设备编号')
                ->clearable()
                ->required(),
            amis()->TextareaControl('device_desc', '设备描述')
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
