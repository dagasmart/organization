<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\WorkerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use Swow\Coroutine;
use Swow\Sync\WaitGroup;

/**
 * 基础-员工类
 *
 * @property WorkerService $service
 */
class WorkerController extends AdminController
{
    protected string $serviceName = WorkerService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton('dialog'),
                ...$this->baseHeaderToolBar(),
                $this->importAction(admin_url('worker/import')),
                $this->exportAction(),
            ])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed', 'left'),
                amis()->TableColumn('worker_name', '姓名')->sortable()->searchable()->set('fixed', 'left'),
                amis()->TableColumn('enterprise_department_job', '机构/部门/职务')
                    ->searchable(
                        amis()->FormControl()->body([
                            amis()->SelectControl('enterprise_id', false)
                                ->options($this->service->getEnterpriseAll())
                                ->placeholder('请选择机构')
                                ->searchable()
                                ->clearable(),
                            amis()->TreeSelectControl('department_id', '部门')
                                ->source(admin_url('biz/worker/${enterprise_id||0}/department/data'))
                                ->disabledOn('${!enterprise_id}')
                                ->onlyChildren(false)
                                ->onlyLeaf(false)
                                ->hideNodePathLabel()
                                ->searchable()
                                ->onEvent([
                                    'change' => [
                                        'actions' => [
                                            [
                                                'actionType' => 'clear',
                                                'componentId' => 'test_job_list',
                                            ],
                                        ],
                                    ],
                                ]),
                            amis()->TreeSelectControl('job_id', '职务')
                                ->source(admin_url('biz/worker/${enterprise_id||0}/department/${department_id||0}/job/data'))
                                ->disabledOn('${!department_id}')
                                ->id('test_job_list')
                                ->onlyChildren(false)
                                ->onlyLeaf(false)
                                // ->hideNodePathLabel()
                                ->resetValue()
                                ->searchable(),
                        ])
                    )
                    ->set('type', 'input-tag')
                    ->set('options', '${enterprise_department_job|json}')
                    ->set('static', true),
                amis()->TableColumn('worker_no', '系统编号')->searchable()->sortable(),
                amis()->TableColumn('id_card', '身份证号')->searchable()->sortable(),
                amis()->TableColumn('avatar', '照片')
                    ->set('src', '${avatar}')
                    ->set('type', 'avatar')
                    ->set('fit', 'cover')
                    ->set('size', 60)
                    ->set('onError', 'return true;')
                    ->set('onEvent', [
                        'click' => [
                            'actions' => [
                                [
                                    'actionType' => 'drawer',
                                    'drawer' => [
                                        'title' => '预览',
                                        'actions' => [],
                                        'closeOnEsc' => true, // esc键关闭
                                        'closeOnOutside' => true, // 域外可关闭
                                        'showCloseButton' => true, // 显示关闭
                                        'body' => [
                                            amis()->Image()
                                                ->src('${avatar}')
                                                ->defaultImage(url(admin_config('admin.default_image')))
                                                ->width('100%')
                                                ->height('100%'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                amis()->TableColumn('mobile', '联系电话')->searchable(),
                //                amis()->TableColumn('area_id', '所属地区id')
                //                    ->searchable(['name'=>'area_id','type'=>'input-city'])
                //                    ->quickEdit(['type'=>'input-city','value'=>'${area_id}'])
                //                    ->set('type','input-city')
                //                    ->set('static',true)
                //                    ->sortable(),
                amis()->TableColumn('alipay_user_id', '刷脸账号')->searchable(),
                amis()->TableColumn('updated_at', '更新时间')->type('datetime')->width(150),
                $this->rowActions('dialog')
                    ->set('align', 'center')
                    ->set('fixed', 'right')
                    ->set('width', 150),
            ])
            ->affixRow([
                //                [
                //                    'type' => 'text',
                //                    'text' => '总计',
                //                    "colSpan" => 3,
                //                ],
                //                [
                //                    'type' => 'tpl',
                //                    "tpl" => '${rows|pick:mobile|sum}'
                //                ]
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->id('worker_form_id')->data(['isEdit' => $isEdit])->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->HiddenControl('id', 'ID')->disabled($isEdit),
                        amis()->TextControl('id_card', '身份证号')
                            ->required()
                            ->validateOnChange()
                            ->validations([
                                'matchRegexp' => '/^[\\d|*]{17}[\\dXx]$/i',
                            ])
                            ->validationErrors([
                                'matchRegexp' => '请输入有效的身份证号码',
                            ])
                            ->addOn($isEdit ?
                                amis()->VanillaAction()->icon('iconfont icon-cdnrefresh')->onEvent([
                                    'click' => [
                                        'actions' => [
                                            [
                                                'actionType' => 'reset',
                                                'componentId' => 'worker_form_id',
                                            ],
                                            [
                                                'actionType' => 'setValue',
                                                'componentName' => 'id_card',
                                                'args' => [
                                                    'value' => '${id_card_enc | base64Decode}',
                                                ],
                                            ],
                                        ],
                                    ],
                                ]) : false
                            )
                            ->onEvent([
                                'blur' => [
                                    'actions' => [
                                        [
                                            'actionType' => 'stopPropagation',
                                            'expression' => '${isEdit}',
                                        ],
                                        [
                                            'actionType' => 'ajax',
                                            'api' => [
                                                'method' => 'GET',
                                                'url' => admin_url('biz/enterprise/worker/${id_card||0}/check'),
                                            ],
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'id',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'id',
                                            'expression' => '${!!event.data.responseResult.responseData.id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'id',
                                            'expression' => '${!event.data.responseResult.responseData.id}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'worker_name',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.worker_name||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'worker_name',
                                            'expression' => '${!!event.data.responseResult.responseData.worker_name}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'worker_name',
                                            'expression' => '${!event.data.responseResult.responseData.worker_name}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'worker_no',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.worker_no||CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'worker_no',
                                            'expression' => '${!!event.data.responseResult.responseData.worker_no}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'worker_no',
                                            'expression' => '${!event.data.responseResult.responseData.worker_no}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'avatar',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.avatar||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'avatar',
                                            'expression' => '${!!event.data.responseResult.responseData.avatar}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'avatar',
                                            'expression' => '${!event.data.responseResult.responseData.avatar}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'party',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.party||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'party',
                                            'expression' => '${!!event.data.responseResult.responseData.party}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'party',
                                            'expression' => '${!event.data.responseResult.responseData.party}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'email',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.email||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'email',
                                            'expression' => '${!!event.data.responseResult.responseData.email}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'email',
                                            'expression' => '${!event.data.responseResult.responseData.email}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'mobile',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.mobile||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'mobile',
                                            'expression' => '${!!event.data.responseResult.responseData.mobile}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'mobile',
                                            'expression' => '${!event.data.responseResult.responseData.mobile}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'sex',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.sex||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'sex',
                                            'expression' => '${!!event.data.responseResult.responseData.sex}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'sex',
                                            'expression' => '${!event.data.responseResult.responseData.sex}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'nation',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.nation||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'nation',
                                            'expression' => '${!!event.data.responseResult.responseData.nation}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'nation',
                                            'expression' => '${!event.data.responseResult.responseData.nation}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'combo',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.combo||null}',
                                            ],
                                        ],
                                        //                                        [
                                        //                                            'actionType' => 'disabled',
                                        //                                            'componentName' => 'combo',
                                        //                                            'expression' => '${!!event.data.responseResult.responseData.combo}'
                                        //                                        ],
                                        //                                        [
                                        //                                            'actionType' => 'enabled',
                                        //                                            'componentName' => 'combo',
                                        //                                            'expression' => '${!event.data.responseResult.responseData.combo}'
                                        //                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'region_id',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.region_id||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'region_id',
                                            'expression' => '${!!event.data.responseResult.responseData.region_id}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'region_id',
                                            'expression' => '${!event.data.responseResult.responseData.region_id}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'address',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.address||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'address',
                                            'expression' => '${!!event.data.responseResult.responseData.address}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'address',
                                            'expression' => '${!event.data.responseResult.responseData.address}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'region_info',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.region_info||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'address_info',
                                            'args' => [
                                                'value' => '${region_info.province} ${region_info.city} ${region_info.district} ${address}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'family',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.family||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'family',
                                            'expression' => '${!!event.data.responseResult.responseData.family}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'family',
                                            'expression' => '${!event.data.responseResult.responseData.family}',
                                        ],
                                    ],
                                ],
                            ]),
                        amis()->TextControl('worker_name', '真实姓名')->id('worker_name')->required(),
                        amis()->HiddenControl('worker_no', '系统编号')
                            ->value('${CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}')
                            ->readOnly(),
                        amis()->TreeSelectControl('party', '政治信仰')
                            ->options(Enum::Party)->value('无党派'),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号码')->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('avatar')
                            ->thumbRatio('1:1')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden' => true, 'h-full' => true])
                            ->imageClassName([
                                'w-52' => true,
                                'h-64' => true,
                                'overflow-hidden' => true,
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-52' => true,
                                'h-64' => true,
                                'overflow-hidden' => true,
                            ]),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->SelectControl('sex', '性别')
                        ->options(Enum::sex())->value(3),
                    amis()->SelectControl('nation', '民族')
                        ->options(Enum::nation()),
                    amis()->SelectControl('work_status', '状态')
                        ->options(Enum::WorkStatus)
                        ->value(1)
                        ->required(),
                ]),
            ]),
            // 机构单位信息
            amis()->Tab()->title('职务信息')->body([
                amis()->ComboControl('combo', false)->items([
                    amis()->SelectControl('enterprise_id', '机构单位${index+1}')
                        ->options($this->service->getEnterpriseAll())
                        ->searchable()
                        ->required(),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->source(admin_url('biz/worker/${combo[index].enterprise_id||0}/department/data'))
                        ->disabledOn('${!combo[index].enterprise_id}')
                        ->onlyChildren(false)
                        ->onlyLeaf(false)
                        ->hideNodePathLabel()
                        ->searchable()
                        ->required()
                        ->onEvent([
                            'change' => [
                                'actions' => [
                                    [
                                        'actionType' => 'clear',
                                        'componentId' => 'test_job_from',
                                    ],
                                ],
                            ],
                        ]),
                    amis()->TreeSelectControl('job_id', '职务')
                        ->source(admin_url('biz/worker/${combo[index].enterprise_id||0}/department/${combo[index].department_id||0}/job/data'))
                        ->disabledOn('${!combo[index].department_id}')
                        ->id('test_job_from')
                        ->onlyChildren(false)
                        ->onlyLeaf(false)
                        ->hideNodePathLabel()
                        ->resetValue()
                        ->searchable()
                        ->required(),
                    amis()->HiddenControl('worker_id')->value('${id}'),
                    amis()->HiddenControl('module')->value(admin_current_module()),
                    amis()->HiddenControl('mer_id')->value(admin_mer_id()),
                ])
                    ->className('border-gray-100 border-dashed')
                    ->mode('horizontal')
                    ->multiLine(false)
                    ->multiple()
                    ->strictMode(false)
                    ->removable()
                    ->required(),
            ]),
            // 家庭情况
            amis()->Tab()->title('家庭情况')->body([
                // amis()->LocationControl()
                // ->ak(env('AMAP_KEY'))
                // ->staticSchema([
                // 'embed' => true
                // ]),
                amis()->InputCityControl('region_id', '所在地区')
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
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('address', '家庭住址'),
                ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${address}')
                    ->static(),
                amis()->Divider()->title('家庭成员')->titlePosition('left'),
                amis()->ComboControl('family', false)->items([
                    amis()->TextControl('family_name', '${index+1}.姓名')
                        ->clearable()
                        ->required(),
                    amis()->SelectControl('family_ties', '关系')
                        ->options(Enum::family())
                        ->clearable()
                        ->required(),
                    amis()->TextControl('family_mobile', '电话')->clearable(),
                ])
                    ->className('border-gray-100 border-dashed')
                    ->mode('horizontal')
                    ->multiLine(false)
                    ->multiple()
                    ->strictMode(false)
                    ->removable(),
            ]),
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
                        amis()->TextControl('worker_name', '真实姓名'),
                        amis()->TextControl('worker_no', '系统编号'),
                        amis()->TextControl('id_card', '身份证号'),
                        amis()->TagControl('party', '政治信仰'),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号码')->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('avatar')
                            ->thumbRatio('1:1')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden' => true, 'h-full' => true])
                            ->imageClassName([
                                'w-52' => true,
                                'h-64' => true,
                                'overflow-hidden' => true,
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-52' => true,
                                'h-64' => true,
                                'overflow-hidden' => true,
                            ]),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->RadiosControl('sex', '性别')
                        ->options(Enum::sex()),
                    amis()->SelectControl('nation_id', '民族')
                        ->options(Enum::nation()),
                    amis()->SelectControl('work_status', '工作状态')
                        ->options(Enum::WorkStatus),
                ]),
            ]),
            // 职务信息
            amis()->Tab()->title('职务信息')->body([
                amis()->ComboControl('combo', false)->items([
                    amis()->SelectControl('enterprise_id', '单位${index+1}')
                        ->options($this->service->getEnterpriseAll())->required(),
                    amis()->HiddenControl('worker_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->options($this->service->departmentData())
                        ->onlyChildren()
                        ->onlyLeaf()
                        ->hideNodePathLabel()
                        ->searchable()
                        ->required(),
                    amis()->TreeSelectControl('job_id', '职务')
                        ->options($this->service->departmentJobData())
                        ->menuTpl('<div class="flex justify-between"><span style="color: var(--button-link-default-font-color);">${label}</span><span class="ml-2 rounded p-1 text-xs text-gray-500 text-center w-full">${tag}</span></div>')
                        ->multiple()
                        ->maxTagCount(5)
                        ->onlyChildren()
                        ->searchable()
                        ->required(),
                    amis()->TagControl('worker_sn', '工号'),
                ])
                    ->className('border-gray-100 border-dashed')
                    ->mode('horizontal')
                    ->multiLine(false)
                    ->strictMode(false)
                    ->multiple()
                    ->removable()
                    ->required(),
            ]),
            // 家庭情况
            amis()->Tab()->title('家庭情况')->body([
                amis()->InputCityControl('region_id', '所在地区')
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
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('address', '家庭住址'),
                ]),
                amis()->HiddenControl('region_info', '地区信息')->id('form_region_info'),
                amis()->TextControl('address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${address}')
                    ->static(),
                amis()->Divider()->title('家庭成员')->titlePosition('left'),
                amis()->ComboControl('family', false)->items([
                    amis()->TextControl('family_name', '${index+1}.姓名')
                        ->clearable()
                        ->required(),
                    amis()->SelectControl('family_ties', '关系')
                        ->options(Enum::family())
                        ->clearable()
                        ->required(),
                    amis()->TextControl('family_mobile', '电话')->clearable(),
                ])
                    ->className('border-gray-100 border-dashed')
                    ->mode('horizontal')
                    ->multiLine(false)
                    ->multiple()
                    ->strictMode(false)
                    ->removable(),
            ]),
        ])->static();
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
     * @return Collection
     */
    public function departmentJobData()
    {
        return $this->service->departmentJobData();
    }

    /**
     * 检查身份证并获取员工信息
     */
    public function EnterpriseWorkerCheck(): JsonResponse|JsonResource
    {
        $id_card = request()->id_card ?? null;
        $res = $this->service->EnterpriseWorkerCheck($id_card);

        return $this->response()->success($res);
    }

    public function importAction($api = null): DialogAction
    {
        return amis()->DialogAction()->label('一键导入')->icon('upload')->dialog(
            amis()->Dialog()->title('一键导入-老师')->body([
                amis()->Action()
                    ->label('演示模板')
                    ->level('light')
                    ->icon('iconfont icon-doc')
                    ->className('float-right')
                    ->actionType('saveAs')
                    ->api(Storage::url('template/worker.csv')),
                amis()->Divider()->color('transparent'),
                amis()->Form()->mode('normal')->api($api)->body([
                    amis()->FileControl()
                        ->name('file')
                        ->label('限制只能上传csv文件')
                        ->accept('.csv')
                        ->receiver('enterprise/worker/import')
                        // ->startChunkApi('enterprise/worker/import')
                        // ->chunkApi('enterprise/worker/import')
                        ->finishChunkApi('enterprise/worker/importChunk')
                        ->required()
                        ->drag()
                        ->onEvent([
                            'remove' => [
                                'actions' => [
                                    [
                                        'actionType' => 'ajax',
                                        'api' => [
                                            'url' => 'enterprise/common/remove',
                                            'method' => 'post',
                                            'data' => [
                                                'path' => '${event.data.value}',
                                            ],
                                            'silent' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                ]),
            ])->actions([])
        );
    }

    public function importChunk(): JsonResponse|JsonResource
    {
        $fileName = request('filename');
        $partList = request('partList');
        $uploadId = request('uploadId');
        $type = request('t', 'uploads');
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $path = $type.'/'.$uploadId.'.'.$ext;
        $fullPath = storage_path('app/public/'.$path);
        make_dir(dirname($fullPath));
        for ($i = 0; $i < count($partList); $i++) {
            $partNumber = $partList[$i]['partNumber'];
            $eTag = $partList[$i]['eTag'];
            $partPath = 'chunk/'.$uploadId.'/'.$partNumber;
            $partETag = md5(Storage::disk('public')->get($partPath));
            if ($eTag != $partETag) {
                return $this->response()->fail('分片上传失败');
            }
            file_put_contents($fullPath, Storage::disk('public')->get($partPath), FILE_APPEND);
        }
        clearstatcache();
        app('files')->deleteDirectory(storage_path('app/public/chunk/'.$uploadId));
        $this->readCsv($fullPath);

        return $this->response()->success(['value' => $path], '上传成功');
    }

    public function import(): JsonResponse|JsonResource
    {
        // try {
        // 验证文件是否存在且不为空
        if (request()->hasFile('file') && request()->file('file')->isValid()) {
            $file = request()->file('file');
            $filename = time().$file->getClientOriginalName(); // 使用时间戳和原始名称作为文件名
            $path = $file->storeAs('files', $filename, 'public'); // 存储到 public 磁盘的 uploads 目录下
            foreach ($this->readCsv(public_storage_path($path)) as $i => $item) {
                echo $i.'行'.json_encode($item, JSON_UNESCAPED_UNICODE).PHP_EOL;
            }

            return $this->response()->success(['value' => $path], '文件上传成功！'); // 返回成功消息
        } else {
            return $this->response()->fail('文件上传失败！');
        }
        // } catch (\Exception $e) {
        // return $this->response()->fail('文件上传失败！');
        // }
    }

    public function readCsv($filePath)
    {

        $wg = new WaitGroup;
        $rows = SimpleExcelReader::create($filePath)->getRows()->toArray();
        foreach ($rows as $index => $row) {
            $wg->add(); // 增加等待计数
            Coroutine::run(function () use ($wg, $index, $row, &$results) {
                try {
                    // 并发执行任务
                    dump($index.'_'.$row);
                } finally {
                    $wg->done(); // 任务完成，减少等待计数
                }
            });
        }

    }
}
