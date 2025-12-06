<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Enums\Enum;
use DagaSmart\School\Services\SchoolService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;

/**
 * 基础-学校类
 *
 * @property SchoolService $service
 */
class SchoolController extends AdminController
{
	protected string $serviceName = SchoolService::class;

	public function list(): Page
    {
		$crud = $this->baseCRUD()
			->filterTogglable()
			->headerToolbar([
				$this->createButton(true)->permission('biz.school.create'),
				...$this->baseHeaderToolBar()
			])
            ->filter($this->baseFilter()->body([
                amis()->TextControl('school_code', '学校代码')
                    ->size('md')
                    ->clearable()
                    ->placeholder('学校代码'),
                amis()->TextControl('school_name', '学校名称')
                    ->size('md')
                    ->clearable()
                    ->placeholder('学校名称'),
                amis()->SelectControl('school_nature', '学校性质')
                    ->options(Enum::Nature)
                    ->clearable(),
                amis()->SelectControl('school_mode', '办学模式')
                    ->options($this->service->getStageAll())
                    ->clearable(),
                amis()->Divider(),
                amis()->DateRangeControl('register_time', '注册登记')
                    ->format('YYYY-MM-DD')
                    ->clearValueOnHidden()
            ]))
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed','left'),
                amis()->TableColumn('school_name', '学校名称')
                    ->searchable()
                    ->width(200)
                    ->set('fixed','left'),
                amis()->TableColumn('school_code', '学校代码'),
                amis()->TableColumn('school_mode', '办学模式')
                    ->set('type', 'select')
                    ->set('options', $this->service->getStageAll())
                    ->filterable(['options' => $this->service->getStageAll()])
                    ->searchable([
                        'name' => 'school_mode',
                        'type' => 'select',
                        'options' => $this->service->getStageAll()
                    ])
                    ->set('static', true),
                amis()->TableColumn('school_nature', '学校性质')
                    ->set('type', 'select')
                    ->set('options', Enum::Nature)
                    ->filterable(['options' => Enum::Nature])
                    ->searchable(['name' => 'school_nature', 'type' => 'select', 'options' => Enum::Nature])
                    ->set('static', true),
                amis()->TableColumn('region', '所属地区')
                    ->searchable(['name'=>'region','type'=>'input-city'])
                    ->set('type','input-city')
                    ->set('static',true)
                    ->set('width', 200)
                    ->sortable(),
                amis()->TableColumn('school_address', '学校地址')
                    ->searchable()
                    ->set('width', 200),
                amis()->TableColumn('location', '位置定位'),
                amis()->TableColumn('register_time', '注册日期')
                    ->quickEdit(['type'=>'input-date','value'=>'${register_time}'])
                    ->set('type','date')
                    ->width(120)
                    ->sortable(),
                amis()->TableColumn('credit_code', '信用代码')->copyable(),
                amis()->TableColumn('legal_person', '学校法人'),
                amis()->TableColumn('contacts_mobile', '联系电话')->searchable(),
                amis()->TableColumn('contacts_email', '联系邮件')->searchable(),
                amis()->TableColumn('updated_at', admin_trans('admin.updated_at'))
                    ->width(100)
                    ->type('datetime')
                    ->sortable(),
                $this->rowActions([
                        //$this->rowAuthButton('drawer', 'md', '授权'),
                        $this->rowShowButton(true),
                        $this->rowEditButton(true),
                        $this->rowDeleteButton(),
                    ])
                    ->set('width', 200)
                    ->set('align', 'center')
                    ->set('fixed', 'right')
            ]);

		return $this->baseList($crud);
	}

	public function form($isEdit = false): Form
	{
        return $this->baseForm()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('school_name', '学校名称')->required(),
                        amis()->TextControl('school_code', '学校代码'),
                        amis()->SelectControl('school_nature', '学校性质')
                            ->options(Enum::Nature)
                            ->required(),
                        amis()->SelectControl('school_mode', '办学模式')
                            ->options($this->service->getStageAll())
                            ->required(),
                        amis()->DateControl('register_time', '注册日期')
                            ->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('school_logo',false)
                            ->thumbRatio('4:3')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden'=>true, 'h-full'=>true])
                            ->imageClassName([
                                'w-80'=>true,
                                'h-60'=>true,
                                'overflow-hidden'=>true
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-80'=>true,
                                'h-60'=>true,
                                'overflow-hidden'=>true
                            ])
                            ->crop([
                                'aspectRatio' => '1.3',
                            ]),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->direction('horizontal')->body([
                    amis()->TextControl('credit_code', '信用代码')
                        ->required(),
                    amis()->TextControl('legal_person', '学校法人'),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('contacts_mobile', '联系电话')
                        ->required(),
                    amis()->TextControl('contacts_email', '联系邮件'),
                ]),
                amis()->Divider(),
                amis()->InputCityControl('region', '所在地区')
                    ->searchable()
                    ->extractValue(false)
                    ->required()
                    ->onEvent([
                        'change' => [
                            'actions' => [
                                [
                                    'actionType'  => 'setValue',
                                    'componentId' => 'form_region_info',
                                    'args'        => [
                                        'value' => '${value}'
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('school_address', '学校地址'),
                amis()->TextControl('school_address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${school_address}')
                    ->static(),
            ]),
            // 学段管理
            amis()->Tab()->title('学段年级')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->CheckboxesControl('school_grade',null)
                        ->checkAll()
                        ->columnsCount(1)
                        ->options($this->service->getGradeAll())
                        ->required()
                ])
            ]),
            // 商户信息
            amis()->Tab()->title('商户信息')->body([
                amis()->SelectControl('module', '模块')
                    ->options(admin_module_all())
                    ->clearable()
                    ->size('md'),
                amis()->SelectControl('mer_id', '商户')
                    ->options($this->service->getMerchantAll())
                    ->clearable()
                    ->size('md'),
            ])->visible(!admin_mer_id()),

        ]);
	}

	public function detail(): Form
    {
		return $this->baseDetail()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('school_name', '学校名称'),
                        amis()->TextControl('school_code', '学校代码'),
                        amis()->SelectControl('school_nature', '学校性质')
                            ->options(Enum::Nature),
                        amis()->SelectControl('school_mode', '办学模式')
                            ->options($this->service->getStageAll()),
                        amis()->DateControl('register_time', '注册日期'),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->Image()
                            ->thumbClassName(['overflow-hidden'=>true, 'w-80'=>true, 'h-60'=>true])
                            ->src('${school_logo}')
                            ->thumbMode('contain')
                            ->enlargeAble(),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->direction('horizontal')->body([
                    amis()->TextControl('credit_code', '信用代码'),
                    amis()->TextControl('legal_person', '学校法人'),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('contacts_mobile', '联系电话'),
                    amis()->TextControl('contacts_email', '联系邮件'),
                ]),
                amis()->Divider(),
                amis()->InputCityControl('region', '所在地区')
                    ->searchable()
                    ->extractValue(false)
                    ->required()
                    ->onEvent([
                        'change' => [
                            'actions' => [
                                [
                                    'actionType'  => 'setValue',
                                    'componentId' => 'form_region_info',
                                    'args'        => [
                                        'value' => '${value}'
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('school_address', '学校地址'),
                amis()->TextControl('school_address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${school_address}')
                    ->static(),
            ]),
            // 学段管理
            amis()->Tab()->title('学段年级')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->CheckboxesControl('school_grade',null)
                        ->checkAll()
                        ->columnsCount(1)
                        ->options($this->service->getGradeAll())
                ])
                ->disabled()
                ->static(false)
            ]),
        ])->static();
	}


    /**
     * 授权按钮
     * @param bool|string $dialog
     * @param string $dialogSize
     * @param string $title
     * @return mixed
     */
    protected function rowAuthButton(bool|string $dialog = false, string $dialogSize = 'md', string $title = ''): mixed
    {
        $title  = $title ?: admin_trans('admin.edit');
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->authForm(true)
                ->api('put:/biz/school/${id}/auth')
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title($title)->body($form)->size($dialogSize)
                );
            } else {
                $action = amis()->DialogAction()->dialog(
                    amis()->Dialog()->title($title)->body($form)->size($dialogSize)
                );
            }
        }

        $action->label($title)->level('link')->visible(admin_user()->administrator());

        return AdminPipeline::handle(AdminPipeline::PIPE_EDIT_ACTION, $action);
    }

    /**
     * 授权表单
     * @param bool $isEdit
     * @return Form
     */
    private function authForm(bool $isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->Alert()
                ->showIcon()
                ->showCloseButton()
                ->style([
                    'padding' => '0.5rem',
                    'borderStyle' => 'dashed',
                ])
                ->body('提示：<p>1.授权给角色时，角色下所有用户可以访问；</p><p>2.授权给用户时，只有授权用户可访问。</p>'),
            amis()->TextControl('id', 'ID')->static(),
            amis()->TextControl('school_code', '学校代码')->static(),
            amis()->TextControl('school_name', '学校名称')->static(),
            amis()->TreeSelectControl('authorize.roles', '授权角色')
                ->multiple()
                //->autoCheckChildren(false)
                //->cascade(false)
                //->withChildren()
                ->onlyChildren()
                ->selectFirst()
                ->options($this->service->roleOption())
                ->onEvent([
                    'change' => [
                        'actions'=> [
                            [
                                'actionType' => 'reset',
                                'componentId' => 'authorize_users',
                            ]
                        ]
                    ]
                ])
                ->required(),
            amis()->SelectControl('authorize.users', '管理员')
                ->id('authorize_users')
                ->multiple()
                ->searchable()
                ->selectMode('associated')
                ->leftMode('tree')
                ->deferApi('#')
                ->leftOptions($this->service->roleOption(true))
                ->options($this->service->roleUserOption())
                ->value(),
        ]);
    }


}
