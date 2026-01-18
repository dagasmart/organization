<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\PatriarchService;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use Fiber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\Exception\ReaderNotOpenedException;
use Spatie\SimpleExcel\SimpleExcelReader;
use SplFileObject;
use Swow\Coroutine;
use Swow\Sync\WaitGroup;
use function Laravel\Prompts\error;
use function Swow\Utils\success;

/**
 * 基础-家长类
 *
 * @property PatriarchService $service
 */
class PatriarchController extends AdminController
{
	protected string $serviceName = PatriarchService::class;

	public function list(): Page
    {
		$crud = $this->baseCRUD()
			->filterTogglable(false)
			->headerToolbar([
				$this->createButton('dialog'),
				...$this->baseHeaderToolBar(),
                //$this->importAction(admin_url('worker/import')),
                $this->exportAction(),
			])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed','left'),
                amis()->TableColumn('patriarch_name', '家长姓名')->sortable()->searchable()->set('fixed','left'),
//                amis()->TableColumn('enterprise_id', '机构')
//                    ->searchable([
//                        'name' => 'enterprise_id',
//                        'type' => 'select',
//                        'multiple' => false,
//                        'searchable' => true,
//                        'options' => $this->service->getEnterpriseAll(),
//                    ])
//                    //->breakpoint('*')
//                    ->set('type','input-tag')
//                    ->set('options',$this->service->getEnterpriseAll())
//                    ->set('value','${enterprise.enterprise_id}')
//                    ->set('fixed','left')
//                    ->set('static', true),

                amis()->TableColumn('id_card','身份证号')->searchable()->sortable(),
                amis()->TableColumn('avatar', '照片')
                    ->set('src','${avatar}')
                    ->set('type','avatar')
                    ->set('fit','cover')
                    ->set('size',60)
                    ->set('onError','return true;')
                    ->set('onEvent', [
                        'click' => [
                            'actions' => [
                                [
                                    'actionType' => 'drawer',
                                    'drawer' => [
                                        'title' => '预览',
                                        'actions' => [],
                                        'closeOnEsc' => true, //esc键关闭
                                        'closeOnOutside' => true, //域外可关闭
                                        'showCloseButton' => true, //显示关闭
                                        'body' => [
                                            amis()->Image()
                                                ->src('${avatar}')
                                                ->defaultImage(url(admin_config('admin.default_image')))
                                                ->width('100%')
                                                ->height('100%'),
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                amis()->TableColumn('mobile', '联系电话')->searchable(),

                amis()->TableColumn('property', '关联学生信息')
                    ->width(100)
                    ->type('property')
                    ->mode('simple')
                    ->separator('')
                    ->items('${property}'),
                amis()->TableColumn('alipay_user_id', '刷脸账号')->searchable(),
                amis()->TableColumn('updated_at', '更新时间')->type('datetime')->width(150),
                $this->rowActions('dialog')
                    ->set('align','center')
                    ->set('fixed','right')
                    ->set('width',150)
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
            amis()->Tab()->title('家长基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->HiddenControl('id', 'ID')->disabled($isEdit),
                        amis()->TextControl('id_card', '身份证号')
                            ->required()
                            ->validateOnChange()
                            ->validations([
                                'matchRegexp' => '/^[\\d|*]{17}[\\dX]$/i',
                            ])
                            ->validationErrors([
                                'matchRegexp' => '请输入有效的身份证号码',
                            ])
                            ->addOn($isEdit ?
                                amis()->VanillaAction()->icon('fa fa-retweet')->onEvent([
                                    'click' => [
                                        'actions' => [
                                            [
                                                'actionType'  => 'reset',
                                                'componentId' => 'worker_form_id',
                                            ],
                                            [
                                                'actionType'  => 'setValue',
                                                'componentName' => 'id_card',
                                                'args' => [
                                                    'value' => '${id_card_enc | base64Decode}'
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
                                            'expression' => '${isEdit}'
                                        ],
                                        [
                                            'actionType' => 'ajax',
                                            'api' => [
                                                'method' => 'GET',
                                                'url' => admin_url('biz/enterprise/worker/${id_card||0}/check'),
                                            ],
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'id',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.id||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'id',
                                            'expression' => '${!!event.data.responseResult.responseData.id}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'id',
                                            'expression' => '${!event.data.responseResult.responseData.id}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'patriarch_name',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.patriarch_name||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'patriarch_name',
                                            'expression' => '${!!event.data.responseResult.responseData.patriarch_name}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'patriarch_name',
                                            'expression' => '${!event.data.responseResult.responseData.patriarch_name}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'patriarch_sn',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.patriarch_sn||CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'patriarch_sn',
                                            'expression' => '${!!event.data.responseResult.responseData.patriarch_sn}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'patriarch_sn',
                                            'expression' => '${!event.data.responseResult.responseData.patriarch_sn}'
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
                                            'expression' => '${!!event.data.responseResult.responseData.avatar}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'avatar',
                                            'expression' => '${!event.data.responseResult.responseData.avatar}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'email',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.email||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'email',
                                            'expression' => '${!!event.data.responseResult.responseData.email}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'email',
                                            'expression' => '${!event.data.responseResult.responseData.email}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'mobile',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.mobile||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'mobile',
                                            'expression' => '${!!event.data.responseResult.responseData.mobile}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'mobile',
                                            'expression' => '${!event.data.responseResult.responseData.mobile}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'sex',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.sex||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'sex',
                                            'expression' => '${!!event.data.responseResult.responseData.sex}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'sex',
                                            'expression' => '${!event.data.responseResult.responseData.sex}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'nation',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.nation||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'nation',
                                            'expression' => '${!!event.data.responseResult.responseData.nation}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'nation',
                                            'expression' => '${!event.data.responseResult.responseData.nation}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'combo',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.combo||null}'
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
                                            'actionType'  => 'setValue',
                                            'componentName' => 'region_id',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.region_id||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'region_id',
                                            'expression' => '${!!event.data.responseResult.responseData.region_id}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'region_id',
                                            'expression' => '${!event.data.responseResult.responseData.region_id}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'address',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.address||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'address',
                                            'expression' => '${!!event.data.responseResult.responseData.address}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'address',
                                            'expression' => '${!event.data.responseResult.responseData.address}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'region_info',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.region_info||null}'
                                            ],
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'address_info',
                                            'args' => [
                                                'value' => '${region_info.province} ${region_info.city} ${region_info.district} ${address}'
                                            ],
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'family',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.family||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'family',
                                            'expression' => '${!!event.data.responseResult.responseData.family}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'family',
                                            'expression' => '${!event.data.responseResult.responseData.family}'
                                        ],
                                    ]
                                ]
                            ]),
                        amis()->TextControl('patriarch_name', '真实姓名')->id('patriarch_name')->required(),
                        amis()->HiddenControl('patriarch_sn', '系统编号')
                            ->value('${CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}')
                            ->readOnly(),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号码')->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('avatar')
                            ->thumbRatio('1:1')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden'=>true, 'h-full'=>true])
                            ->imageClassName([
                                'w-52'=>true,
                                'h-64'=>true,
                                'overflow-hidden'=>true
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-52'=>true,
                                'h-64'=>true,
                                'overflow-hidden'=>true
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
        ])->onEvent([
//            'submitSucc' => [
//                'actions' => [
//                    [
//                        'actionType' => 'custom',
//                        'script' => 'window.$owl.refreshAmisPage();'
//                    ],
//                ]
//            ]
        ]);
    }

	public function detail(): Form
    {
		return $this->baseDetail()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('家长基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('patriarch_name', '真实姓名'),
                        amis()->TextControl('patriarch_sn', '家长编号'),
                        amis()->TextControl('id_card', '身份证号'),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号码')->required(),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('avatar')
                            ->thumbRatio('1:1')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden'=>true, 'h-full'=>true])
                            ->imageClassName([
                                'w-52'=>true,
                                'h-64'=>true,
                                'overflow-hidden'=>true
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-52'=>true,
                                'h-64'=>true,
                                'overflow-hidden'=>true
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
        ])->static();
	}

    /**
     * 检查身份证并获取员工信息
     * @return JsonResponse|JsonResource
     */
    public function EnterpriseWorkerCheck(): JsonResponse|JsonResource
    {
        $id_card = request()->id_card ?? null;
        $res = $this->service->EnterpriseWorkerCheck($id_card);
        return $this->response()->success($res);
    }

}
