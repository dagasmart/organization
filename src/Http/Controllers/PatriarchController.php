<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\PatriarchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

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
                // $this->importAction(admin_url('worker/import')),
                $this->exportAction(),
            ])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed', 'left'),
                amis()->TableColumn('patriarch_name', '家长姓名')->sortable()->searchable()->set('fixed', 'left'),
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

                amis()->TableColumn('property', '关联学生信息')
                    ->width(100)
                    ->type('property')
                    ->mode('simple')
                    ->separator('')
                    ->items('${property}'),
                amis()->TableColumn('alipay_user_id', '刷脸账号')->searchable(),
                amis()->TableColumn('updated_at', '更新时间')->type('datetime')->width(150),
                $this->rowActions('dialog')
                    ->set('align', 'center')
                    ->set('fixed', 'right')
                    ->set('width', 150),
            ])
            ->affixRow([
                // [
                //    'type' => 'text',
                //    'text' => '总计',
                //    "colSpan" => 2,
                // ],
                // [
                //    'type' => 'tpl',
                //    "tpl" => '${rows|pick:total|sum}'
                // ]
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
                                                'url' => admin_url('biz/enterprise/patriarch/${id_card||0}/check'),
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
                                            'componentName' => 'patriarch_name',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.patriarch_name||null}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'patriarch_name',
                                            'expression' => '${!!event.data.responseResult.responseData.patriarch_name}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'patriarch_name',
                                            'expression' => '${!event.data.responseResult.responseData.patriarch_name}',
                                        ],
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'patriarch_sn',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.patriarch_sn||CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'patriarch_sn',
                                            'expression' => '${!!event.data.responseResult.responseData.patriarch_sn}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'patriarch_sn',
                                            'expression' => '${!event.data.responseResult.responseData.patriarch_sn}',
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
                        amis()->TextControl('patriarch_name', '真实姓名')->id('patriarch_name')->required(),
                        amis()->TextControl('patriarch_sn', '系统编号')
                            ->value('${CONCATENATE("E", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}')
                            ->readOnly(),
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
                amis()->Divider()->color('var(--colors-brand-6)'),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->SelectControl('sex', '性别')
                        ->options(Enum::sex())->value(3),
                    amis()->SelectControl('nation', '民族')
                        ->options(Enum::nation()),
                    amis()->SwitchControl('is_primary', '监护人')
                        ->onText('是')
                        ->offText('否')
                        ->value(true),
                ]),
            ]),
            amis()->Tab()->title('关联学生信息')->body([
                amis()->Alert()->body([
                    amis()->Tpl()->className('float-left')->tpl('<span class="text-danger">提示：</span>家长本人可通过小程序自行绑定学生关系'),
                    amis()->Button()
                        ->icon('iconfont icon-edap-tool-btn-add')
                        ->level('link')
                        ->label('添加')
                        ->className('float-right')
                        ->actionType('drawer')
                        ->drawer([
                            'title' => '添加',
                            'closeOnOutside' => true,
                            'closeOnEsc' => true,
                            'actions' => [],
                            'body' => [
                                amis()->Form()->mode('inline')->body([
                                    amis()->SelectControl('search_enterprise_id', '机构')
                                        ->id('searchEnterpriseId')
                                        ->mode('horizontal')
                                        ->options($this->service->getEnterpriseAll())
                                        ->autoComplete(false)
                                        ->labelWidth('12%')
                                        ->searchable()
                                        ->clearable()
                                        ->required(),
                                    amis()->GroupControl()->mode('horizontal')->body([
                                        amis()->SelectControl('search_grade_id', '年级')
                                            ->id('searchGradeId')
                                            ->mode('horizontal')
                                            ->source(admin_url('biz/enterprise/${search_enterprise_id||0}/grade'))
                                            ->selectMode('group')
                                            ->autoComplete(false)
                                            ->labelWidth('25%')
                                            ->clearable()
                                            ->disabledOn('${!search_enterprise_id}')
                                            ->onEvent([
                                                'change' => [
                                                    'actions' => [
                                                        [
                                                            'actionType' => 'reset',
                                                            'componentId' => 'searchGradeId',
                                                        ],
                                                        [
                                                            'actionType' => 'clear',
                                                            'componentId' => 'searchClassesId',
                                                        ],
                                                    ],
                                                ],
                                            ]),
                                        amis()->SelectControl('search_classes_id', '班级')
                                            ->id('searchClassesId')
                                            ->mode('horizontal')
                                            ->sendOn('${search_grade_id > 0}')
                                            ->initFetch(false)
                                            ->autoComplete(false)
                                            ->labelWidth('30%')
                                            ->source(admin_url('biz/enterprise/${search_enterprise_id||0}/grade/${search_grade_id||0}/classes'))
                                            ->labelClassName('w-1/3')
                                            ->disabledOn('${!search_grade_id}')
                                            ->clearable(),
                                    ]),
                                    amis()->GroupControl()->mode('horizontal')->body([
                                        amis()->TextControl('search_student_name', '姓名')
                                            ->mode('horizontal')
                                            ->disabledOn('${!search_enterprise_id}')
                                            ->autoComplete(false)
                                            ->labelWidth('25%')
                                            ->clearable(),
                                        amis()->TextControl('search_id_card', '身份证号')
                                            ->mode('horizontal')
                                            ->autoComplete(false)
                                            ->labelWidth('30%')
                                            ->disabledOn('${!search_enterprise_id}')
                                            ->clearable(),
                                    ]),
                                    amis()->Divider()->color('var(--colors-brand-6)'),
                                    amis()->CRUD2Cards()
                                        ->api(admin_url('biz/enterprise/student/search?enterprise_id=${search_enterprise_id}&grade_id=${search_grade_id}&classes_id=${search_classes_id}&student_name=${search_student_name}&id_card=${search_id_card}'))
                                        ->silentPolling()
                                        ->columnsCount(2)
                                        ->autoFillHeight()
                                        ->card([
                                            'style' => [
                                                'border' => '1px solid var(--colors-brand-9)',
                                                'boxShadow' => 'inset 0 0 10px 0 var(--colors-brand-10)',
                                            ],
                                            'header' => [
                                                'title' => '${student.student_name}',
                                                'subTitle' => '${student.sex_as}　${student.nation_as}',
                                                'subTitlePlaceholder' => '暂无说明',
                                                'avatar' => '${student.avatar}',
                                                'avatarClassName' => 'overflow-hidden w-12 h-12 thumb rounded-full b-3x m-l m-r',
                                            ],
                                            'body' => [
                                                [
                                                    'name' => '${grade.grade_name}',
                                                    'label' => '年级',
                                                ],
                                                [
                                                    'name' => '${classes.classes_name}',
                                                    'label' => '班级',
                                                ],
                                            ],
                                            'actions' => [
                                                amis()->Button()->icon('iconfont icon-edap-tool-btn-add')->label('添加'),
                                            ],
                                        ]),
                                ]),
                            ],
                        ]),
                ]),
                amis()->Cards()
                    ->id('your_parent_component_id')
                    ->source('${child}')
                    ->columnsCount(3)
                    ->placeholder('暂无关联学生')   // ✅ 空状态提示
                    ->card([
                        'style' => [
                            'border' => '1px solid var(--colors-brand-9)',
                            'boxShadow' => 'inset 0 0 10px 0 var(--colors-brand-10)',
                        ],
                        'header' => [
                            'title' => '${rel.student.student_name}',
                            'subTitle' => '${rel.student.sex_as} ${rel.student.nation_as}',
                            'subTitlePlaceholder' => '暂无说明',
                            'avatar' => '${rel.student.avatar}',
                            'avatarClassName' => 'overflow-hidden w-12 h-12 thumb rounded-full b-3x m-l m-r',
                        ],
                        'body' => [
                            [
                                'name' => '${rel.enterprise.enterprise_name}',
                                'label' => '学校',
                            ],
                            [
                                'name' => '${rel.grade.grade_name}',
                                'label' => '年级',
                            ],
                            [
                                'name' => '${rel.classes.classes_name}',
                                'label' => '班级',
                            ],
                        ],
                        'actions' => [
                            [
                                'label' => '移除',
                                'actionType' => 'button',
                                'level' => 'link',
                                'icon' => 'iconfont icon-trash-alt',
                                'confirmTitle' => '操作提示',
                                'confirmText' => '是否移除关联关系？',
                                'onEvent' => [
                                    'click' => [
                                        'actions' => [
                                            [
                                                'actionType' => 'custom',
                                                'script' => '
    // 1. 打开浏览器 F12 控制台，查看 event.data 和 context 的真实结构
    console.log("当前事件数据:", event.data);
    console.log("当前上下文:", event.context);

    // 2. 获取当前行数据（优先取 item，兼容不同版本）
    var currentRow = event.data.item || event.data;

    // 3. 【关键】通过 event.context.scoped 获取父级数据域中的 child 数组
    // 这种方式比直接用 event.data.child 更稳定，能跨作用域获取数据
    var scoped = event.context.scoped;
    var childList = [];

    // 尝试从当前作用域获取 child
    if (scoped && scoped.data && scoped.data.child) {
        childList = scoped.data.child;
    } else if (event.data && event.data.child) {
        // 兜底：如果 scoped 拿不到，再从 event.data 拿
        childList = event.data.child;
    }

    console.log("获取到的原始 childList:", childList);

    // 4. 过滤掉当前点击的行
    var newList = childList.filter(function(item) {
        // 确保这里的字段路径与你实际的数据结构一致
        return item.student_id !== currentRow.student_id;
    });

    console.log("过滤后的 newList:", newList);

    // 5. 重新设置数据源
    doAction({
        actionType: "setValue",
        componentId: "your_parent_component_id", // ✅ 关键：明确告诉 amis 更新哪个组件的数据
        args: {
            value: {
                child: newList
            }
        }
    });
'
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                        ],
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
                    amis()->SelectControl('nation', '民族')
                        ->options(Enum::nation()),
                    amis()->SwitchControl('is_primary', '监护人')
                        ->onText('是')
                        ->offText('否')
                        ->static(false)
                        ->disabled(),

                ]),
                amis()->Divider(),
                amis()->DateTimeControl('updated_at', '更新时间'),
            ]),
        ])->static();
    }

    /**
     * 检查身份证并获取家长信息
     */
    public function EnterprisePatriarchCheck(): JsonResponse|JsonResource
    {
        $id_card = request()->id_card ?? null;
        $res = $this->service->EnterprisePatriarchCheck($id_card);

        return $this->response()->success($res);
    }
}
