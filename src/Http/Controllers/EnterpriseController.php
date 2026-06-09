<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 基础-机构类
 *
 * @property EnterpriseService $service
 */
class EnterpriseController extends AdminController
{
    protected string $serviceName = EnterpriseService::class;

    public function index()
    {
        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        return $this->response()->success($this->page());
    }

    /**
     * 左侧分类树，右侧分发列表
     */
    public function page(): Page
    {
        return amis()->Page()->body(
            amis()->Grid()->columns([
                amis()->Flex()->className('h-full')->items([
                    // $this->region(),
                    $this->nature(),
                    $this->stage(),
                ])->direction('column')->set('md', 3),

                $this->list()->set('md', 7),

                amis()->Flex()->className('h-full')->items([
                    $this->chart(),
                    $this->chart(),
                    $this->region(),
                ])->direction('column')->set('md', 2),
                // $this->relevance()->set('md', 2),
                // amis()->Flex()->className('h-full')->items([
                //     $this->relevance(),
                //     $this->relevance(),
                // ])->direction('column'),
            ])
        );
    }

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->api($this->getListGetDataPath().'&enterprise_name=${enterprise_name}&enterprise_code=${enterprise_code}&enterprise_nature=${enterprise_nature}&enterprise_mode=${enterprise_mode}&enterprise_address=${enterprise_address}&register_time=${register_time}&contacts_mobile=${contacts_mobile}&contacts_email=${contacts_email}&region=${region}')
            ->id('crud_record')
            ->filterTogglable()
            ->headerToolbar([
                $this->createButton(true)->permission('biz.enterprise.create'),
                ...$this->baseHeaderToolBar(),
            ])
            ->filter($this->baseFilter()->body([
                amis()->Flex()->items([
                    amis()->TextControl('enterprise_name', '机构名称')
                        ->size('md')
                        ->clearable()
                        ->placeholder('机构名称'),
                    amis()->InputCityControl('region', '地区城市')
                        ->placeholder('请选择地区城市'),
                    amis()->DateRangeControl('register_time', '注册登记')
                        ->format('YYYY-MM-DD')
                        ->clearValueOnHidden(),
                ])->direction('column'),

            ]))
            ->checkOnItemClick() // 核心：开启点击整行时同步勾选左侧按钮

            ->onEvent([
                'fetchInited' => [
                    'actions' => [
                        [
                            'actionType' => 'select',
                            'componentId' => 'crud_record',
                            'args' => [
                                'key' => 'selected',
                                'condition' => '${ARRAYINCLUDES([__enterprise_id], id)}',
                            ],
                        ],
                    ],
                ],
                'selectedChange' => [
                    'actions' => [
                        //                        [
                        //                            'actionType' => 'reload',
                        //                            'target' => 'chartWorker?id=${event.data.rowItem.id|json}',
                        //                        ],
                        [
                            'actionType' => 'setValue',
                            'componentName' => '__enterprise_id',
                            'args' => [
                                'value' => '${event.data.selectedItems[0].id||null}',
                            ],
                        ],
                        [
                            'actionType' => 'selected', // ✅ 正确：触发选中行为
                            'componentId' => 'crud_record', // 指向当前的 CRUD 组件
                        ],
                    ],
                ],
            ])
            ->selectable()
            ->multiple(false)
            ->syncLocation(false)
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed', 'left'),
                amis()->TableColumn('enterprise_name', '机构名称')
                    ->searchable()
                    ->width(200),
                amis()->TableColumn('enterprise_code', '机构代码')->searchable(),
                amis()->TableColumn('enterprise_nature', '机构性质')
                    ->set('type', 'select')
                    ->set('options', $this->service->natureOption())
//                    ->filterable([
//                        'options' => $this->service->natureOption(),
//                        'mini' => true,
//                        'clearable' => true, // 允许清空
//                        'submitOnChange' => true,
//                    ])
//                    ->searchable([
//                        'name' => 'enterprise_nature',
//                        'type' => 'select',
//                        'options' => $this->service->natureOption(),
//                        'clearable' => true,
//                        'submitOnChange' => true,
//                        'onEvent' => [
//                            'change' => [
//                                'actions' => [
//                                    [
//                                        'actionType' => 'setValue',
//                                        'componentId' => 'enterpriseNature',
//                                        'args' => [
//                                            'value' => '${event.data.value | number:0}',
//                                        ],
//                                    ],
//                                ],
//                            ],
//                        ],
//                    ])
                    ->set('static', true),
                amis()->TableColumn('enterprise_mode', '开办模式')
                    ->set('type', 'select')
                    ->set('options', $this->service->getStageAll())
//                    ->filterable([
//                        'options' => $this->service->getStageAll(),
//                        'mini' => true,
//                        'clearable' => true, // 允许清空
//                        'submitOnChange' => true,
//                    ])
//                    ->searchable([
//                        'name' => 'enterprise_mode',
//                        'type' => 'select',
//                        'options' => $this->service->getStageAll(),
//                        'clearable' => true,
//                        'submitOnChange' => true,
//                        'onEvent' => [
//                            'change' => [
//                                'actions' => [
//                                    [
//                                        'actionType' => 'setValue',
//                                        'componentId' => 'enterpriseMode',
//                                        'args' => [
//                                            'value' => '${event.data.value | number:0}',
//                                        ],
//                                    ],
//                                ],
//                            ],
//                        ],
//                    ])
                    ->set('static', true),
                amis()->TableColumn('region', '所属地区')
                    ->searchable(['name' => 'region', 'type' => 'input-city'])
                    ->set('type', 'input-city')
                    ->set('static', true)
                    ->set('width', 200)
                    ->sortable(),
                amis()->TableColumn('enterprise_address', '机构地址')
                    ->searchable()
                    ->set('width', 200),
                amis()->TableColumn('location', '位置定位'),
                amis()->TableColumn('register_time', '注册日期')
                    ->quickEdit(['type' => 'input-date', 'value' => '${register_time}'])
                    ->set('type', 'date')
                    ->width(120)
                    ->searchable(
                        amis()->DateRangeControl('register_time'),
                    )
                    ->sortable(),
                amis()->TableColumn('credit_code', '信用代码')->copyable(),
                amis()->TableColumn('legal_person', '机构法人'),
                amis()->TableColumn('contacts_mobile', '联系电话')->searchable(),
                amis()->TableColumn('contacts_email', '联系邮件')->searchable(),
                amis()->TableColumn('updated_at', admin_trans('admin.updated_at'))
                    ->width(100)
                    ->type('datetime')
                    ->sortable(),
                $this->rowActions([
                    $this->rowDepartmentButton('drawer', 'md', '部门'),
                    $this->rowJobButton('drawer', 'md', '职务'),
                    $this->rowAuthButton('drawer', 'md', '授权'),
                    $this->rowShowButton(true),
                    $this->rowEditButton(true),
                    $this->rowDeleteButton(),
                ])
                    ->set('width', 150)
                    ->set('align', 'center')
                    ->set('fixed', 'right'),
            ]);

        return $this->baseList($crud);
    }

    /**
     * 左侧地区导航，用于筛选右侧列表
     */
    public function region()
    {
        return amis()->Card()->className('w-full h-full')->body([
            amis()->TreeControl('region', false)
                // ->deferApi('basic/region/${value||0}/children')
                ->options($this->service->regionAll())
                ->labelField('name')
                ->valueField('code')
                ->nodeBehavior(['check', 'unfold'])
                ->initiallyOpen(false)
                ->autoCheckChildren()
                ->withChildren()
                ->joinValues()
                ->rootLabel('所处地区')
                ->hideRoot(false)
                ->cascade(),
        ]);
    }

    /**
     * 左侧性质导航，用于筛选右侧列表
     */
    public function nature()
    {
        return amis()->Card()->className('w-full h-full')->body([
            amis()->TreeControl('enterprise_nature', false)
                ->id('enterpriseNature')
                // ->deferApi('basic/region/${value||0}/children')
                ->options($this->service->natureOption())
                ->nodeBehavior(['check', 'unfold'])
                ->initiallyOpen(false)
                ->autoCheckChildren()
                ->withChildren()
                ->joinValues()
                ->rootLabel('机构性质')
                ->hideRoot(false)
                ->cascade(),
        ]);
    }

    /**
     * 左侧开办模式导航，用于筛选右侧列表
     */
    public function stage()
    {
        return amis()->Card()->className('w-full h-full')->body([
            amis()->TreeControl('enterprise_mode', false)
                ->id('enterpriseMode')
                // ->deferApi('basic/region/${value||0}/children')
                ->options($this->service->getStageAll())
                ->nodeBehavior(['check', 'unfold'])
                ->initiallyOpen(false)
                ->autoCheckChildren()
                ->withChildren()
                ->joinValues()
                ->rootLabel('开办模式')
                ->hideRoot(false)
                ->cascade(),
        ]);
    }

    /**
     * 左侧开办模式导航，用于筛选右侧列表
     */
    public function chart()
    {

        return amis()->Card()->className('w-full h-full')->body([
            amis()->Chart()->name('chartWorker')->height('100%')->config([
                'color' => generateColors(null, null, 10),
                'backgroundColor' => '',
                'title' => ['text' => '员工人数'],
                'tooltip' => ['trigger' => 'axis'],
                'loading' => false,
                'xAxis' => [
                    'type' => 'category',
                    'boundaryGap' => false,
                    'data' => ['Mon', 'Tue', 'Wed', 'Thu'],
                ],
                'axisLine' => ['lineStyle' => ['color' => '#000']],
                'yAxis' => ['type' => 'value'],
                'grid' => ['left' => '5%', 'right' => '3%', 'top' => 30, 'bottom' => 20],
                'legend' => ['data' => ['Visits', 'Bounce Rate']],
                'series' => [
                    [
                        'name' => false,
                        'data' => '${option1}',
                        'type' => 'line',
                        'areaStyle' => [],
                        'smooth' => true,
                        'symbol' => 'none',
                    ],
                    [
                        'name' => false,
                        'data' => '${option2}',
                        'type' => 'line',
                        'areaStyle' => [],
                        'smooth' => true,
                        'symbol' => 'none',
                    ],
                ],
            ])
                ->api('biz/enterprise/chart/data?enterprise_id=${__enterprise_id}')
                ->interval(3000),
            amis()->HiddenControl('__enterprise_id')->resetValue(0),
        ]);
    }

    public function chartData()
    {
        $data = [];
        for ($i = 0; $i <= 4; $i++) {
            $data['option1'][] = rand(50, 200);
            $data['option2'][] = rand(50, 200);
        }

        return $this->response()->success($data);
    }

    /**
     * 左侧分类导航，用于筛选右侧列表
     */
    public function relevance()
    {
        return amis()->Card()->className('w-full h-full')->body([
            amis()->Tabs()->tabs([
                amis()->Tab()->title('部门管理')->body([
                    amis()->TreeControl()
                        ->creatable()
                        ->removable()
                        ->editable()
                        ->createBtnLabel('新建权限项')
                        ->addControls([
                            amis()->TextControl(),
                        ])
                        ->searchable()
                        ->clearable(),
                ]),
                amis()->Tab()->title('职务管理')->body([
                    amis()->TableControl('jobCRUD')
                        ->columns([
                            amis()->TableColumn('job_name', '职务名称'),
                            amis()->TableColumn('parent_id', '上级职务'),
                        ])
                        ->childrenAddable()
                        ->needConfirm()
                        ->draggable()
                        ->addable()
                        ->addApi('put:'.admin_url('biz/enterprise/${id:1}/department/save'))
                        ->editable()
                        ->removable()
                        ->onEvent([
                            'addSuccess' => [
                                'actions' => [
                                    [
                                        'actionType' => 'toast',
                                        'args' => [
                                            'msgType' => 'info',
                                            'msg' => '${event.data | json:0}',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                ]),
            ]),
        ]);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('enterprise_name', '机构名称')->required(),
                        amis()->TextControl('enterprise_code', '机构代码'),
                        amis()->SelectControl('enterprise_nature', '机构性质')
                            ->options($this->service->natureOption())
                            ->clearable()
                            ->required(),
                        amis()->SelectControl('enterprise_mode', '开办模式')
                            ->options($this->service->stageOption())
                            ->source(admin_url('biz/enterprise/stage/${enterprise_nature||0}/option'))
                            ->disabledOn('${!enterprise_nature||null}')
                            ->clearable()
                            ->required(),
                        amis()->DateControl('register_time', '注册日期')
                            ->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('enterprise_logo', false)
                            ->thumbRatio('4:3')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden' => true, 'h-full' => true])
                            ->imageClassName([
                                'w-80' => true,
                                'h-60' => true,
                                'overflow-hidden' => true,
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-80' => true,
                                'h-60' => true,
                                'overflow-hidden' => true,
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
                    amis()->TextControl('legal_person', '机构法人'),
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
                    ->value(admin_region_code())
                    ->required()
                    ->onEvent([
                        'change' => [
                            'actions' => [
                                [
                                    'actionType' => 'setValue',
                                    'componentId' => 'form_region_info',
                                    'args' => [
                                        'value' => '${value}',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('enterprise_address', '机构地址'),
                amis()->TextControl('enterprise_address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${enterprise_address}')
                    ->static(),
            ]),
            // 学段管理
            amis()->Tab()->title('学段年级')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->CheckboxesControl('enterprise_grade', null)
                        ->source(admin_url('biz/enterprise/stage/${enterprise_mode||0}/grade/all'))
                        ->options($this->service->getGradeAll())
                        ->checkAll()
                        ->columnsCount(1)
                        ->disabledOn('${!enterprise_mode}')
                        ->required(),
                ]),
            ])->visible(is_school_module()),
            // 商户信息
            amis()->Tab()->title('商户信息')->body([
                amis()->SelectControl('module', '模块')
                    ->options($this->service->moduleOption())
                    ->value(admin_current_module())
                    ->clearable()
                    ->size('md'),
                amis()->SelectControl('mer_id', '商户')
                    ->source(admin_url('system/merchant/${module||0}/all'))
                    ->options($this->service->getMerchantAll())
                    ->clearable()
                    ->size('md'),
            ])->visible(! admin_mer_id()),

        ])->onEvent([
            'submitSucc' => [
                'actions' => [
                    [
                        'actionType' => 'custom',
                        'script' => 'window.$owl.refreshAmisPage();',
                    ],
                ],
            ],
        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('enterprise_name', '机构名称'),
                        amis()->TextControl('enterprise_code', '机构代码'),
                        amis()->SelectControl('enterprise_nature', '机构性质')
                            ->options($this->service->natureOption()),
                        amis()->SelectControl('enterprise_mode', '开办模式')
                            ->options($this->service->getStageAll()),
                        amis()->DateControl('register_time', '注册日期'),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->Image()
                            ->thumbClassName(['overflow-hidden' => true, 'w-80' => true, 'h-60' => true])
                            ->src('${enterprise_logo}')
                            ->thumbMode('contain')
                            ->enlargeAble(),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->direction('horizontal')->body([
                    amis()->TextControl('credit_code', '信用代码'),
                    amis()->TextControl('legal_person', '机构法人'),
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
                                    'actionType' => 'setValue',
                                    'componentId' => 'form_region_info',
                                    'args' => [
                                        'value' => '${value}',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('enterprise_address', '机构地址'),
                amis()->TextControl('enterprise_address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${enterprise_address}')
                    ->static(),
            ]),
            // 学段管理
            amis()->Tab()->title('学段年级')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->CheckboxesControl('enterprise_grade', null)
                        ->checkAll()
                        ->columnsCount(1)
                        ->options($this->service->getGradeAll()),
                ])
                    ->disabled()
                    ->static(false),
            ])->visible(is_school_module()),
        ])->static();
    }

    /**
     * 授权按钮
     */
    protected function rowAuthButton(bool|string $dialog = false, string $dialogSize = 'md', string $title = ''): mixed
    {
        $title = $title ?: admin_trans('admin.edit');
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->authForm(true)
                ->api('put:/biz/enterprise/${id}/auth')
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
            amis()->TextControl('enterprise_code', '机构代码')->static(),
            amis()->TextControl('enterprise_name', '机构名称')->static(),
            amis()->TreeSelectControl('authorize.roles', '授权角色')
                ->multiple()
                // ->autoCheckChildren(false)
                // ->cascade(false)
                // ->withChildren()
                ->onlyChildren()
                ->selectFirst()
                ->options($this->service->roleOption())
                ->onEvent([
                    'change' => [
                        'actions' => [
                            [
                                'actionType' => 'reset',
                                'componentId' => 'authorize_users',
                            ],
                        ],
                    ],
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

    /**
     * 授权按钮
     */
    protected function rowDepartmentButton(bool|string $dialog = false, string $dialogSize = 'md', string $title = ''): mixed
    {
        $title = $title ?: admin_trans('admin.edit');
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->departmentForm(true)
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title($title)->body($form)->size($dialogSize)->actions()
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
     * 部门表单
     */
    private function departmentForm(bool $isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->Alert()
                ->showIcon()
                ->showCloseButton()
                ->body('提示：部门至少保留一项'),
            amis()->TreeControl('department_id', false)
                ->source(admin_url('biz/enterprise/${id||0}/department/data'))
                ->menuTpl('${label}<span class="text-gray-400 rounded-lg ml-1 p-1 text-xs text-left w-14">${tag}</span>')
                ->heightAuto()
                ->creatable()
                ->addControls([
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TextControl('department_name', '部门名称')->required(),
                    amis()->TreeSelectControl('parent_id', '上级部门')
                        ->source(admin_url('biz/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData())
                        ->disabledOn('${!!parent}')
                        ->value('${parent.id}'),
                    amis()->TextareaControl('remark', '部门描述'),
                    amis()->SwitchControl('department_state', '状态')
                        ->onText('显示')
                        ->offText('隐藏')
                        ->value(1),
                    amis()->NumberControl('department_sort', '排序')
                        ->size('xs')
                        ->min(1)
                        ->max(255)
                        ->value(10)
                        ->required(),
                ])
                ->addApi(admin_url('biz/enterprise/department/save'))
                ->editable()
                ->editControls([
                    amis()->HiddenControl('id'),
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TextControl('department_name', '部门名称'),
                    amis()->TreeSelectControl('parent_id', '上级部门')
                        ->source(admin_url('biz/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData()),
                    amis()->TextareaControl('remark', '部门描述'),
                    amis()->SwitchControl('department_state', '状态')
                        ->onText('显示')
                        ->offText('隐藏')
                        ->value('${state}'),
                    amis()->NumberControl('department_sort', '排序')
                        ->size('xs')
                        ->min(1)
                        ->max(255)
                        ->value(10)
                        ->required(),
                ])
                ->editApi(admin_url('biz/enterprise/department/save'))
                ->removable()
                ->deleteApi(admin_url('biz/enterprise/department/${id}/delete'))
                ->showOutline()
                ->searchable()
                ->required(),

        ]);
    }

    /**
     * 职务按钮
     */
    protected function rowJobButton(bool|string $dialog = false, string $dialogSize = 'md', string $title = ''): mixed
    {
        $title = $title ?: admin_trans('admin.edit');
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->jobForm(true)
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title($title)->body($form)->size($dialogSize)->actions()
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
     * 职务表单
     */
    private function jobForm(bool $isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->Alert()
                ->showIcon()
                ->showCloseButton()
                ->body('提示：部门职务至少保留一项'),
            amis()->TreeControl('job_id', false)
                ->source(admin_url('biz/enterprise/${id||0}/job/data'))
                ->menuTpl('${label}<span class="text-gray-400 rounded-lg ml-1 p-1 text-xs text-left w-14">${tag}</span>')
                ->heightAuto()
                ->creatable()
                ->addControls([
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->source(admin_url('biz/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData()),
                    amis()->TextControl('job_name', '职务')->required(),
                    amis()->TreeSelectControl('parent_id', '上级')
                        ->source(admin_url('biz/enterprise/${enterprise_id||0}/job/data'))
                        ->options($this->service->jobData())
                        ->disabledOn('${!!parent}')
                        ->value('${parent.id}'),
                    amis()->TextareaControl('remark', '备注'),
                    amis()->SwitchControl('job_state', '状态')
                        ->onText('显示')
                        ->offText('隐藏')
                        ->value(1),
                    amis()->NumberControl('job_sort', '排序')
                        ->size('xs')
                        ->min(1)
                        ->max(255)
                        ->value(10)
                        ->required(),
                ])
                ->addApi(admin_url('biz/enterprise/job/save'))
                ->editable()
                ->editControls([
                    amis()->HiddenControl('id'),
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->source(admin_url('biz/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData()),
                    amis()->TextControl('job_name', '职务'),
                    amis()->TreeSelectControl('parent_id', '上级')
                        ->source(admin_url('biz/enterprise/${enterprise_id}/job/data'))
                        ->options($this->service->jobData()),
                    amis()->TextareaControl('remark', '描述'),
                    amis()->SwitchControl('job_state', '状态')
                        ->onText('显示')
                        ->offText('隐藏')
                        ->value('${state}'),
                    amis()->NumberControl('job_sort', '排序')
                        ->size('xs')
                        ->min(1)
                        ->max(255)
                        ->value(10)
                        ->required(),
                ])
                ->editApi(admin_url('biz/enterprise/job/save'))
                ->removable()
                ->deleteApi(admin_url('biz/enterprise/job/${id}/delete'))
                ->showOutline()
                ->searchable()
                ->required(),

        ]);
    }

    public function stageOption(): array
    {
        return $this->service->stageOption();
    }

    public function getGradeAll(): array
    {
        return $this->service->getGradeAll();
    }

    /**
     * 部门数据
     *
     * @return array
     */
    public function departmentData()
    {
        return $this->service->departmentData();
    }

    /**
     * 职务数据
     *
     * @return array
     */
    public function jobData()
    {
        return $this->service->jobData();
    }

    /**
     * 部门职务数据
     *
     * @return array
     */
    public function departmentJobData()
    {
        return $this->service->departmentJobData();
    }

    /**
     * 部门保存
     *
     * @return JsonResponse|JsonResource
     */
    public function departmentSave()
    {
        $res = $this->service->departmentSave();

        return $this->response()->success($res);
    }

    /**
     * 职务保存
     *
     * @return JsonResponse|JsonResource
     */
    public function jobSave()
    {
        $res = $this->service->jobSave();

        return $this->response()->success($res);
    }

    /**
     * 删除部门
     *
     * @return JsonResponse|JsonResource
     */
    public function departmentDelete()
    {
        $res = $this->service->departmentDelete();

        return $this->response()->success($res);
    }

    /**
     * 删除职务
     *
     * @return JsonResponse|JsonResource
     */
    public function jobDelete()
    {
        $res = $this->service->jobDelete();

        return $this->response()->success($res);
    }
}
