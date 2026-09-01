<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * 基础-机构类
 *
 * @property EnterpriseService $service
 */
class EnterpriseController extends AdminController
{
    protected string $serviceName = EnterpriseService::class;

    public function index(): JsonResponse|JsonResource
    {
        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        return $this->response()->success($this->page());
    }

    public function page(): Page
    {
        return $this->basePage()
            ->name('page-home')
            ->data(['dashboard_key' => base64_encode($this->getListPath())])
            ->toolbarClassName('relative z-50 text-right top-6 hidden')
            ->toolbar([
                amis()->HiddenControl('readonly')->value(true),
                amis()->Button()
                    ->level('${readonly ? "primary" : "danger"}')
                    ->icon('iconfont icon-${readonly ? "bcmlayout" : "eye"}')
                    ->className('fixed shadow right-8')
                    ->label('${readonly ? "布局" : "预览"}')
                    ->onEvent([
                        'click' => [
                            'debounce' => 300,
                            'actions' => [
                                [
                                    'actionType' => 'setValue',
                                    'componentName' => 'readonly',
                                    'args' => [
                                        'value' => '${!readonly}', // ✅ 简化取反表达式
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->Button()
                    ->level('info')
                    ->icon('iconfont icon-waiting')
                    ->className('fixed shadow right-32')
                    ->label('还原')
                    ->hiddenOn('${readonly}')
                    ->actionType('drawer')
                    ->drawer([
                        'title' => '还原布局',
                        'closeOnEsc' => true,
                        'closeOnOutside' => true,
                        'size' => 'sm',
                        'body' => [
                            amis()->Alert()->body('提示：还原点只保存每日最后一次变更记录')->showCloseButton(),
                            amis()->TextControl('dashboard_key', '页面')->readOnly(),
                        ],
                    ]),
                amis()->Button()
                    ->level('success')
                    ->icon('iconfont icon-edap-tool-btn-add')
                    ->className('fixed shadow right-56')
                    ->label('创建')
                    ->hiddenOn('${readonly}')
                    ->actionType('drawer')
                    ->drawer([
                        'title' => '创建组件',
                        'closeOnEsc' => true,
                        'closeOnOutside' => true,
                        'size' => 'sm',
                        'body' => amis()->Form()->mode('normal')
                            ->api([
                                'url' => '/layout/grid/create',
                                'method' => 'post',
                            ])
                            ->body([
                                amis()->Alert()
                                    ->body('提示：选择组件类型并配置基础属性，之后编辑操作')
                                    ->showCloseButton(),

                                amis()->TextControl('dashboard_key', '页面')
                                    ->value(base64_encode($this->getListPath()))
                                    ->readOnly(),

                                amis()->SelectControl('type', '组件类型')
                                    ->options([
                                        ['label' => '统计卡片', 'value' => 'stat'],
                                        ['label' => '图表', 'value' => 'chart'],
                                        ['label' => '表格', 'value' => 'table'],
                                    ])
                                    ->required(),

                                amis()->TextControl('title', '标题')
                                    ->placeholder('请输入组件标题'),

                                amis()->GroupControl()->body([
                                    amis()->NumberControl('w', '宽度')
                                        ->min(1)->max(24)->value(6),
                                    amis()->NumberControl('h', '高度')
                                        ->min(1)->max(12)->value(4),
                                ]),
                            ])
                            ->onEvent([
                                'submitSucc' => [
                                    'actions' => [
                                        ['actionType' => 'closeDialog'],
                                        ['actionType' => 'reload', 'target' => 'page-home-grid'],
                                    ],
                                ],
                            ]),
                    ]),
            ])
            ->body([
                amis()->GridStack()
                    ->id('page-home-grid') // 👈 必须绑定 ID，供事件精准定位
                    ->name('page-home-grid') // 👈 必须绑定 ID，供事件精准定位
                    ->readonly('${readonly}')
                    ->style('background: transparent;')
                    ->options([
                        'column' => 24,
                        'cellHeight' => 'auto',
                        'margin' => 5,
                        'animate' => true,
                        'float' => false,
                        'virtualRender' => false,
                    ])
                    ->onEvent([
                        // ✅ 1. 复制事件：请求后端生成新 Body 并更新视图
                        'widgetCopy' => [
                            'actions' => [
                                [
                                    'actionType' => 'ajax',
                                    'debounce' => 500,
                                    'api' => [
                                        'url' => '/layout/grid/copy',
                                        'method' => 'post',
                                        'data' => [
                                            'dashboard_key' => base64_encode($this->getListPath()),
                                            'originalId' => '${event.data.originalId}',
                                            'newId' => '${event.data.newId}',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        // ✅ 1. 编辑事件：请求后端生成新 Body 并更新视图
                        'widgetEdit' => [
                            'actions' => [
                                amis()->DrawerAction()
                                    ->data(['widget' => '${event.data.widget}'])
                                    ->drawer([
                                        'title' => '编辑组件',
                                        'closeOnEsc' => true,
                                        'closeOnOutside' => true,
                                        'size' => 'sm',
                                        'body' => amis()->Form()
                                            ->mode('normal')
                                            ->api([
                                                'url' => '/layout/grid/edit',
                                                'method' => 'post',
                                            ])
                                            ->body([
                                                amis()->Alert()->body('提示：编辑只保存组件基本属性')->showCloseButton(),
                                                amis()->HiddenControl('dashboard_key', '页面key')
                                                    ->value(base64_encode($this->getListPath()))
                                                    ->readOnly(),
                                                amis()->TextControl('widget.id', '组件key')
                                                    ->readOnly(),
                                                amis()->GroupControl()->body([
                                                    amis()->NumberControl('widget.w', '宽度')
                                                        ->min(1)->max(24)->value(6),
                                                    amis()->NumberControl('widget.h', '高度')
                                                        ->min(1)->max(12)->value(4),
                                                ]),
                                                amis()->GroupControl()->body([
                                                    amis()->SwitchControl('widget.locked', '锁定位置')
                                                        ->onText('是')
                                                        ->offText('否'),
                                                    amis()->SwitchControl('widget.noMove', '自由移动')
                                                        ->onText('是')
                                                        ->offText('否'),
                                                ]),
                                                amis()->GroupControl()->body([
                                                    amis()->SwitchControl('widget.noResize', '拖拽缩放')
                                                        ->onText('是')
                                                        ->offText('否'),
                                                    amis()->SwitchControl('widget.autoPosition', '自动补位')
                                                        ->onText('是')
                                                        ->offText('否'),
                                                ]),
                                                amis()->GroupControl()->body([
                                                    amis()->SwitchControl('widget.sizeToContent', '自适应高度')
                                                        ->onText('是')
                                                        ->offText('否'),
                                                ]),
                                            ])
                                            ->onEvent([
                                                'submitSucc' => [
                                                    'actions' => [
                                                        ['actionType' => 'closeDialog'],
                                                        ['actionType' => 'custom', 'script' => 'window.setTimeout(() => window.$owl.refreshAmisPage(), 0)'],
                                                    ],
                                                ],
                                            ]),
                                    ]),
                            ],
                        ],
                        // ✅ 1. 复制事件：请求后端生成新 Body 并更新视图
                        'widgetDelete' => [
                            'actions' => [
                                [
                                    'actionType' => 'ajax',
                                    'debounce' => 500,
                                    'api' => [
                                        'url' => '/layout/grid/delete',
                                        'method' => 'post',
                                        'data' => [
                                            'dashboard_key' => base64_encode($this->getListPath()),
                                            'originalId' => '${event.data.id}',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        // ✅ 2. 布局变更事件：持久化物理位置到后端
                        'layoutChange' => [
                            'actions' => [
                                [
                                    'actionType' => 'ajax',
                                    'debounce' => 800,
                                    'api' => [
                                        'url' => '/layout/grid/save',
                                        'method' => 'post',
                                        'data' => [
                                            'dashboard_key' => base64_encode($this->getListPath()),
                                            'layout' => '${event.data.layout}',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ])
                    ->bodyMap($this->bodyMap())
                    ->layoutData($this->layoutData()),
            ]);
    }

    public function bodyMap(): array
    {
        $dashboard_key = base64_encode($this->getListPath());
//        $record = $this->service->bodyMap($dashboard_key);
        if (empty($record)) {
            $defaultGrid = $this->templateGrid('default');
            $tags = $defaultGrid->pluck('template', 'id')->toArray();
            return array_map(function ($method) {
                return method_exists($this, $method) ? $this->$method() : null;
            }, $tags);
//            if ($record) {
//                $this->service->saveGridBody($dashboard_key, $tags, $record);
//            }
        }

        return array_map(function ($method) {
            return method_exists($this, $method) ? $this->$method() : null;
        }, $record);
    }

    public function layoutData(): array
    {
        $dashboard_key = base64_encode($this->getListPath());
//        $record = $this->service->pageGrid($dashboard_key);
        if (empty($record)) {
            $defaultGrid = $this->templateGrid('default');
//            $tags = $defaultGrid->pluck('template', 'id')->toArray();
//            $bodys = array_map(function ($method) {
//                return method_exists($this, $method) ? $this->$method() : null;
//            }, $tags);
//            if ($bodys) {
//                $this->service->saveGridBody($dashboard_key, $tags, $bodys);
//            }
            $record = $defaultGrid?->except(['template'])?->toArray();
        }

        return $record;
    }

    public function templateGrid($key = null): Collection
    {
        $record = [];
        // 当前页默认
        $record['default'] = [
            ['id' => 'enterprise_grid_1', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 5, 'minH' => 3, 'sizeToContent' => false, 'template' => 'nature'],
            ['id' => 'enterprise_grid_2', 'x' => 0, 'y' => 6, 'w' => 4, 'h' => 6, 'minH' => 3, 'sizeToContent' => false, 'template' => 'stage'],
            ['id' => 'enterprise_grid_3', 'x' => 4, 'y' => 0, 'w' => 15, 'h' => 11, 'sizeToContent' => false, 'template' => 'list'],
            ['id' => 'enterprise_grid_4', 'x' => 19, 'y' => 0, 'w' => 5, 'h' => 2, 'sizeToContent' => false, 'template' => 'nav'],
            ['id' => 'enterprise_grid_5', 'x' => 19, 'y' => 3, 'w' => 5, 'h' => 3, 'sizeToContent' => false, 'template' => 'chart'],
            ['id' => 'enterprise_grid_6', 'x' => 19, 'y' => 7, 'w' => 5, 'h' => 3, 'sizeToContent' => false, 'template' => 'chart'],
            ['id' => 'enterprise_grid_7', 'x' => 19, 'y' => 11, 'w' => 5, 'h' => 3, 'sizeToContent' => false, 'template' => 'chart'],
        ];
        $collect = collect($record);
        if (empty($key)) {
            return $collect->flatten(1)->unique('id', true)->values();
        }

        // ✅ 用 collect() 包一层，保证返回类型统一
        return collect($collect[$key] ?? []);
    }



    /**
     * 左侧分类树，右侧分发列表
     */
    public function page2(): Page
    {
        return amis()->Page()->body(
            amis()->Grid()->columns([
                amis()->Flex()->className('h-full')->items([
                    // $this->region(),
                    $this->nature(),
                    $this->stage(),
                ])->direction('column')->set('md', 2),

                $this->list()->set('md', 7),

                amis()->Flex()->className('h-full')->items([
                    $this->nav()->className('h-1/5'),
                    $this->chart(),
                    $this->chart(),
                    // $this->region(),
                ])->direction('column')->set('md', 3),
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
            ->api($this->getListGetDataPath().'&enterprise_name=${enterprise_name}&enterprise_code=${enterprise_code}&nature_id=${nature_id}&stage_id=${stage_id}&enterprise_address=${enterprise_address}&register_time=${register_time}&contacts_mobile=${contacts_mobile}&contacts_email=${contacts_email}&region=${region}&creator=${creator}')
            ->id('crud_record')
            ->filterTogglable()
            ->headerToolbar([
                $this->createButton(true),
                ...$this->baseHeaderToolBar(),
            ])
            ->filter($this->baseFilter()->body([
                amis()->Flex()->items([
                    amis()->GroupControl()->body([
                        amis()->TextControl('enterprise_name', '机构名称')
                            ->size('md')
                            ->clearable()
                            ->placeholder('机构名称'),
                        amis()->SwitchControl('creator')->onText('我是')->option('创建者'),
                    ]),
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
                amis()->TableColumn('enterprise_name', extend_trans('organization.enterprise_name'))
                    ->searchable()
                    ->width(200),
                amis()->TableColumn('enterprise_code', '机构代码')->searchable(),
                amis()->TableColumn('nature_id', '机构性质')
                    ->set('type', 'select')
                    ->set('options', $this->service->natureOption())
//                    ->filterable([
//                        'options' => $this->service->natureOption(),
//                        'mini' => true,
//                        'clearable' => true, // 允许清空
//                        'submitOnChange' => true,
//                    ])
//                    ->searchable([
//                        'name' => 'nature_id',
//                        'type' => 'select',
//                        'options' => $this->service->natureOption(),
//                        'clearable' => true,
//                        'submitOnChange' => true,
//                        'onEvent' => [
//                            'change' => [
//                                'actions' => [
//                                    [
//                                        'actionType' => 'setValue',
//                                        'componentId' => 'enterpriseNatureId',
//                                        'args' => [
//                                            'value' => '${event.data.value | number:0}',
//                                        ],
//                                    ],
//                                ],
//                            ],
//                        ],
//                    ])
                    ->set('static', true),
                amis()->TableColumn('stage_id', '开办模式')
                    ->set('type', 'select')
                    ->set('options', $this->service->getStageAll())
//                    ->filterable([
//                        'options' => $this->service->getStageAll(),
//                        'mini' => true,
//                        'clearable' => true, // 允许清空
//                        'submitOnChange' => true,
//                    ])
//                    ->searchable([
//                        'name' => 'stage_id',
//                        'type' => 'select',
//                        'options' => $this->service->getStageAll(),
//                        'clearable' => true,
//                        'submitOnChange' => true,
//                        'onEvent' => [
//                            'change' => [
//                                'actions' => [
//                                    [
//                                        'actionType' => 'setValue',
//                                        'componentId' => 'enterpriseStageId',
//                                        'args' => [
//                                            'value' => '${event.data.value | number:0}',
//                                        ],
//                                    ],
//                                ],
//                            ],
//                        ],
//                    ])
                    ->set('width', 120)
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
                amis()->TableColumn('social_credit_code', '信用代码')->copyable(),
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
                    $this->rowEditButton(true, 'md', '${!is_creator ? "更正" : "编辑"}')->disabledOn('${!is_creator}'),
                    $this->rowDeleteButton('${!is_creator ? "解绑" : "删除"}'),
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
            amis()->TreeControl('nature_id', false)
                ->id('enterpriseNatureId')
                // ->deferApi('basic/region/${value||0}/children')
                ->source(admin_url('extension/enterprise/nature/${stage_id||0}/option'))
                ->options($this->service->getNatureAll())
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
            amis()->TreeControl('stage_id', false)
                ->id('enterpriseStageId')
                // ->deferApi('basic/region/${value||0}/children')
                ->source(admin_url('extension/enterprise/stage/${nature_id||0}/option'))
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
                ->api('extension/enterprise/chart/data?enterprise_id=${__enterprise_id}')
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
                        ->addApi('put:'.admin_url('extension/enterprise/${id:1}/department/save'))
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

    public function nav()
    {
        return amis()->Card()->className('w-full h-full')->body([
            amis()->Page()->data(['items' => $this->service->navQuick()])->body([
                amis()->GridNav()->source('${items}')->itemClassName('follow')->columnNum(3)->square()->border(true),
            ]),
        ]);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->mode('horizontal')->data([
            'isEdit' => $isEdit,
            'nature_id' => '${nature_id}',
            'stage_id' => '${stage_id}',
        ])->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->HiddenControl('id', 'ID')->disabled(),
                        amis()->TextControl('social_credit_code', '统一信用代码')
                            ->required()
                            ->validateOnChange()
                            ->validations([
                                'matchRegexp' => '/^[0-9A-HJ-NPQRTUWXY]{2}\\d{6}[0-9A-HJ-NPQRTUWXY]{10}$/',
                            ])
                            ->validationErrors([
                                'matchRegexp' => '格式不对，应为18位字母数字组合',
                            ])
                            ->onEvent([
                                'change' => [
                                    // ✅ 新增：防抖，避免输入过程中频繁请求
                                    'debounce' => 300,
                                    'actions' => [
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'social_credit_code',
                                            'args' => [
                                                'value' => '${social_credit_code | upperCase}',
                                            ],
                                        ],
                                        // ✅ 新增：校验当前字段，失败则自动阻断后续所有动作
                                        [
                                            'actionType' => 'validate', // validate天然具有校验失败，阻断后续动作的功能
                                            'componentName' => 'social_credit_code',
                                        ],
                                        // ✅ 新增：编辑模式下直接跳过（保留原有逻辑）
                                        // ✅ 新增：额外判断长度，防止正则通过但值不完整的情况
                                        [
                                            'actionType' => 'stopPropagation',
                                            'expression' => '${isEdit || !social_credit_code || social_credit_code.length !== 18}',
                                        ],
                                        [
                                            'actionType' => 'ajax',
                                            'api' => [
                                                'method' => 'GET',
                                                'url' => admin_url('extension/enterprise/${social_credit_code||0}/check'),
                                            ],
                                            'loading' => true,
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'id',
                                            'args' => [
                                                'value' => '${event.data.responseData.id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'id',
                                            'expression' => '${!!event.data.responseData.id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'id',
                                            'expression' => '${!event.data.responseData.id}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'enterprise_name',
                                            'args' => [
                                                'value' => '${event.data.responseData.enterprise_name||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'enterprise_name',
                                            'expression' => '${!!event.data.responseData.enterprise_name}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'enterprise_name',
                                            'expression' => '${!event.data.responseData.enterprise_name}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'nature_id',
                                            'args' => [
                                                'value' => '${event.data.responseData.nature_id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'nature_id',
                                            'expression' => '${!!event.data.responseData.nature_id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'nature_id',
                                            'expression' => '${!event.data.responseData.nature_id}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'stage_id',
                                            'args' => [
                                                'value' => '${event.data.responseData.stage_id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'stage_id',
                                            'expression' => '${!!event.data.responseData.stage_id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'stage_id',
                                            'expression' => '${!event.data.responseData.stage_id}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'register_time',
                                            'args' => [
                                                'value' => '${event.data.responseData.register_time|toDate:YYYY-MM-DD}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'register_time',
                                            'expression' => '${!!event.data.responseData.register_time}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'register_time',
                                            'expression' => '${!event.data.responseData.register_time}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'enterprise_logo',
                                            'args' => [
                                                'value' => '${event.data.responseData.enterprise_logo||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'enterprise_logo',
                                            'expression' => '${!!event.data.responseData.enterprise_logo}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'enterprise_logo',
                                            'expression' => '${!event.data.responseData.enterprise_logo}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'enterprise_code',
                                            'args' => [
                                                'value' => '${event.data.responseData.enterprise_code||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'enterprise_code',
                                            'expression' => '${!!event.data.responseData.enterprise_code}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'enterprise_code',
                                            'expression' => '${!event.data.responseData.enterprise_code}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'legal_person',
                                            'args' => [
                                                'value' => '${event.data.responseData.legal_person||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'legal_person',
                                            'expression' => '${!!event.data.responseData.legal_person || false}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'legal_person',
                                            'expression' => '${!event.data.responseData.legal_person || true}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'contacts_mobile',
                                            'args' => [
                                                'value' => '${event.data.responseData.contacts_mobile||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'contacts_mobile',
                                            'expression' => '${!!event.data.responseData.contacts_mobile}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'contacts_mobile',
                                            'expression' => '${!event.data.responseData.contacts_mobile}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'contacts_email',
                                            'args' => [
                                                'value' => '${event.data.responseData.contacts_email||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'contacts_email',
                                            'expression' => '${!!event.data.responseData.contacts_email}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'contacts_email',
                                            'expression' => '${!event.data.responseData.contacts_email}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'region',
                                            'args' => [
                                                'value' => '${event.data.responseData.region||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'region',
                                            'expression' => '${!!event.data.responseData.region}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'region',
                                            'expression' => '${!event.data.responseData.region}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'region_info',
                                            'args' => [
                                                'value' => '${event.data.responseData.region_info||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'region_info',
                                            'expression' => '${!!event.data.responseData.region_info}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'region_info',
                                            'expression' => '${!event.data.responseData.region_info}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'enterprise_address',
                                            'args' => [
                                                'value' => '${event.data.responseData.enterprise_address||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'enterprise_address',
                                            'expression' => '${!!event.data.responseData.enterprise_address}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'enterprise_address',
                                            'expression' => '${!event.data.responseData.enterprise_address}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'enterprise_address_info',
                                            'args' => [
                                                'value' => '${event.data.responseData.enterprise_address_info||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'enterprise_address_info',
                                            'expression' => '${!!event.data.responseData.enterprise_address_info}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'enterprise_address_info',
                                            'expression' => '${!event.data.responseData.enterprise_address_info}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'grade_id',
                                            'args' => [
                                                'value' => '${event.data.responseData.grade_id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'grade_id',
                                            'expression' => '${!!event.data.responseData.grade_id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'grade_id',
                                            'expression' => '${!event.data.responseData.grade_id}',
                                        ],
                                    ],
                                ],
                            ]),
                        amis()->TextControl('enterprise_name', '机构名称')
                            ->disabledOn('${!!isEdit && !is_creator}')
                            ->required(),
                        amis()->SelectControl('nature_id', '机构性质')
                            ->options($this->service->natureOption())
                            ->disabledOn('${!!isEdit && !is_creator}')
                            ->clearable()
                            ->required(),
                        amis()->SelectControl('stage_id', '开办模式')
                            ->source(admin_url('extension/enterprise/stage/${nature_id||0}/option'))
                            ->options($this->service->stageOption())
                            ->disabledOn('${!!isEdit && !is_creator}')
                            ->clearable()
                            ->required(),
                        amis()->DateControl('register_time', '注册日期')
                            ->format('YYYY-MM-DD')
                            ->clearable()
                            ->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('enterprise_logo', false)
                            ->disabledOn('${!!isEdit && !is_creator}')
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
                    amis()->TextControl('enterprise_code', is_school_module() ? '学校编码' : '单位编码')
                        ->disabledOn('${!!isEdit && !is_creator}')
                        ->required(),
                    amis()->TextControl('legal_person', '机构法人')
                        ->disabledOn('${!!isEdit && !is_creator}'),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('contacts_mobile', '联系电话')
                        ->disabledOn('${!!isEdit && !is_creator}')
                        ->required(),
                    amis()->TextControl('contacts_email', '联系邮件')
                        ->disabledOn('${!!isEdit && !is_creator}'),
                ]),
                amis()->Divider(),
                amis()->InputCityControl('region', '所在地区')
                    ->searchable()
                    ->extractValue(false)
                    ->value(admin_region_code())
                    ->disabledOn('${!!isEdit && !is_creator}')
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
                amis()->TextControl('enterprise_address', '机构地址')->disabledOn('${!!isEdit && !is_creator}'),
                amis()->TextControl('enterprise_address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${enterprise_address}')
                    ->disabledOn('${!!isEdit && !is_creator}')
                    ->static(),
            ]),
            // 学段管理
            amis()->Tab()->title('学段年级')->body([
                amis()->Alert()->showCloseButton()->body('请在【基本信息】选择‹开办模式›后，再选择学段年级。'),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->CheckboxesControl('grade_id', null)
                        ->source(admin_url('extension/enterprise/stage/${stage_id||0}/grade/all'))
                        ->options($this->service->getGradeAll())
                        ->disabledOn('${!!isEdit && !is_creator}')
                        ->checkAll()
                        ->columnsCount(1)
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
                        amis()->TextControl('social_credit_code', '社会信用代码'),
                        amis()->TextControl('enterprise_name', '机构名称'),
                        amis()->SelectControl('nature_id', '机构性质')
                            ->options($this->service->natureOption()),
                        amis()->SelectControl('stage_id', '开办模式')
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
                    amis()->TextControl('enterprise_code', '机构编码'),
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
                    amis()->CheckboxesControl('grade_id', null)
                        ->source(admin_url('extension/enterprise/stage/${stage_id||0}/grade/all'))
                        ->options($this->service->getGradeAll())
                        ->disabledOn('${!!isEdit && !is_creator}')
                        ->checkAll()
                        ->columnsCount(1)
                        ->disabled()
                        ->static(false),
                ]),
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
                ->api('put:/extension/enterprise/${id}/auth')
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

        $action->label($title)->level('link')->visible(admin_can('auth'));

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
     * 部门按钮
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

        $action->label($title)->level('link')->visible(admin_can('department'));

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
                ->source(admin_url('extension/enterprise/${id||0}/department/data'))
                ->menuTpl('${label}<span class="text-gray-400 rounded-lg ml-1 p-1 text-xs text-left w-14">${tag}</span>')
                ->heightAuto()
                ->creatable()
                ->creatableOn('${!!is_creator}')
                ->addControls([
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TextControl('department_name', '部门名称')->required(),
                    amis()->TreeSelectControl('parent_id', '上级部门')
                        ->source(admin_url('extension/enterprise/${enterprise_id||0}/department/data'))
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
                ->addApi(admin_url('extension/enterprise/department/save'))
                ->editable()
                ->editableOn('${!!is_creator}')
                ->editControls([
                    amis()->HiddenControl('id'),
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TextControl('department_name', '部门名称'),
                    amis()->TreeSelectControl('parent_id', '上级部门')
                        ->source(admin_url('extension/enterprise/${enterprise_id||0}/department/data'))
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
                ->editApi(admin_url('extension/enterprise/department/save'))
                ->removable()
                ->removableOn('${!!is_creator}')
                ->deleteApi(admin_url('extension/enterprise/department/${id}/delete'))
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
        $action->label($title)->level('link')->visible(admin_can('job'));

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
                ->source(admin_url('extension/enterprise/${id||0}/job/data'))
                ->menuTpl('${label}<span class="text-gray-400 rounded-lg ml-1 p-1 text-xs text-left w-14">${tag}</span>')
                ->heightAuto()
                ->creatable()
                ->addControls([
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->source(admin_url('extension/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData()),
                    amis()->TextControl('job_name', '职务')->required(),
                    amis()->TreeSelectControl('parent_id', '上级')
                        ->source(admin_url('extension/enterprise/${enterprise_id||0}/job/data'))
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
                ->addApi(admin_url('extension/enterprise/job/save'))
                ->editable()
                ->editControls([
                    amis()->HiddenControl('id'),
                    amis()->HiddenControl('enterprise_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->source(admin_url('extension/enterprise/${enterprise_id||0}/department/data'))
                        ->options($this->service->departmentData()),
                    amis()->TextControl('job_name', '职务'),
                    amis()->TreeSelectControl('parent_id', '上级')
                        ->source(admin_url('extension/enterprise/${enterprise_id}/job/data'))
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
                ->editApi(admin_url('extension/enterprise/job/save'))
                ->removable()
                ->deleteApi(admin_url('extension/enterprise/job/${id}/delete'))
                ->showOutline()
                ->searchable()
                ->required(),

        ]);
    }

    public function natureOption(): array
    {
        return $this->service->natureOption();
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

    public function enterpriseCheck(): JsonResponse|JsonResource
    {
        $social_credit_code = request()->social_credit_code ?? null;

        usccByCode($social_credit_code);

        $res = $this->service->enterpriseCheck($social_credit_code);

        return $this->response()->success($res);
    }
}
