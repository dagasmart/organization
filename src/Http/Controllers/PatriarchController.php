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
                // amis()->TableColumn('alipay_user_id', '刷脸账号')->searchable(),
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
                                    amis()->PaginationWrapper()->mode('horizontal')->perPage(1)->body([
                                        amis()->CRUD2Cards()
                                            ->api(admin_url('biz/enterprise/student/search?enterprise_id=${search_enterprise_id}&grade_id=${search_grade_id}&classes_id=${search_classes_id}&student_name=${search_student_name}&id_card=${search_id_card}'))
                                            ->silentPolling()
                                            ->columnsCount(2)
                                            ->defaultParams([
                                                'perPage' => 1,
                                            ])
                                            ->pagination(true)
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
                                                    [
                                                        'label' => '添加',
                                                        'actionType' => 'button',
                                                        'level' => 'link',
                                                        'icon' => 'iconfont icon-edap-tool-btn-add',
                                                        'confirmTitle' => '操作提示',
                                                        'confirmText' => '是否添加<b class="text-danger"> ${student.student_name} </b>关联关系?',
                                                        'onEvent' => [
                                                            'click' => [
                                                                'actions' => [
                                                                    [
                                                                        'actionType' => 'custom',
                                                                        'script' => '
                                                                            // ✅ 修复1: 安全获取上下文和数据
            var data = context?.data || event?.data || {};
            var currentItem = data.item || data;

            // ✅ 修复2: 如果依然取不到，尝试从事件目标获取
            if (!currentItem || !currentItem.student) {
                console.error("无法获取当前卡片数据，完整上下文:", JSON.parse(JSON.stringify(data)));
                doAction({
                    actionType: "toast",
                    args: { msgType: "error", msg: "数据获取失败，请查看控制台" }
                });
                return;
            }

            // 获取外层已有的关联列表（安全取值）
            var currentList = data.childes || [];

            // 【调试打印】
            console.log("===== 纯前端添加调试信息 =====");
            console.log("1. 当前卡片数据 (currentItem):", JSON.parse(JSON.stringify(currentItem)));
            console.log("2. 外层已有列表 (currentList):", JSON.parse(JSON.stringify(currentList)));
            console.log("=============================");

            // 防重复校验
            var exists = currentList.some(function(item) {
                var id = item.student_id || (item.rel && item.student && item.student.student_id);
                return id === currentItem.student.student_id;
            });

            if (exists) {
                doAction({
                    actionType: "toast",
                    args: { msgType: "warning", msg: "该学生已在关联列表中" }
                });
                return;
            }

            // 数据结构对齐（根据控制台打印结果调整）
            var formattedItem = currentItem;

            // 追加新数据
            var newList = currentList.concat([formattedItem]);

            console.log("3. 即将写入的新列表:", JSON.parse(JSON.stringify(newList)));

            // ✅ 通过 componentId 精准更新外层 Cards
            doAction({
                actionType: "setValue",
                componentId: "component_cards_child_id",
                args: {
                    value: {
                        childes: newList
                    }
                }
            });
                                                                        ',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ]),
                                    ]),
                                ]),
                            ],
                        ]),
                ]),
                amis()->TextareaControl('childes', '关联学生')->id('component_child_id'),
                amis()->Cards()
                    ->id('component_cards_child_id')
                    ->source('${childes}')
                    ->columnsCount(3)
                    ->placeholder('暂无关联学生')   // ✅ 空状态提示
                    ->card([
                        'style' => [
                            'border' => '1px solid var(--colors-brand-9)',
                            'boxShadow' => 'inset 0 0 10px 0 var(--colors-brand-10)',
                        ],
                        'header' => [
                            'title' => '${student.student_name}',
                            'subTitle' => '${student.sex_as} ${student.nation_as}',
                            'subTitlePlaceholder' => '暂无说明',
                            'avatar' => '${student.avatar}',
                            'avatarClassName' => 'overflow-hidden w-12 h-12 thumb rounded-full b-3x m-l m-r',
                        ],
                        'body' => [
                            [
                                'name' => '${enterprise.enterprise_name}',
                                'label' => '学校',
                            ],
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
                            [
                                'label' => '移除',
                                'actionType' => 'button',
                                'level' => 'link',
                                'icon' => 'iconfont icon-trash-alt',
                                'confirmTitle' => '操作提示',
                                'confirmText' => '是否移除<b class="text-danger"> ${student.student_name} </b>关联关系?',
                                'onEvent' => [
                                    'click' => [
                                        'actions' => [
                                            [
                                                'actionType' => 'custom',
                                                'script' => '
                                                    const currentRow = event.data.item || event.data;
                                                    const childList = event.data.childes || [];
                                                    const newList = childList.filter(function(item) {
                                                        return item.student_id !== currentRow.student_id;
                                                    });
                                                    doAction({
                                                        actionType: "setValue",
                                                        componentId: "component_cards_child_id", // ✅ 关键：明确告诉 amis 更新哪个组件的数据
                                                        args: {
                                                            value: {
                                                                childes: newList
                                                            }
                                                        }
                                                    });
                                                    doAction({
                                                        actionType: "setValue",
                                                        componentId: "component_child_id",
                                                        args: {
                                                            value: newList
                                                        }
                                                    });
                                                ',
                                            ],
                                        ],
                                    ],
                                ],
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
