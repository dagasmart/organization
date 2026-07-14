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
                                'change' => [
                                    // ✅ 新增：防抖，避免输入过程中频繁请求
                                    'debounce' => 300,
                                    'actions' => [
                                        [
                                            'actionType' => 'setValue',
                                            'componentName' => 'id_card',
                                            'args' => [
                                                'value' => '${id_card | upperCase}',
                                            ],
                                        ],
                                        // ✅ 新增：校验当前字段，失败则自动阻断后续所有动作
                                        [
                                            'actionType' => 'validate', // validate天然具有校验失败，阻断后续动作的功能
                                            'componentName' => 'id_card',
                                        ],
                                        // ✅ 新增：编辑模式下直接跳过（保留原有逻辑）
                                        [
                                            'actionType' => 'stopPropagation',
                                            'expression' => '${isEdit}',
                                        ],
                                        // ✅ 新增：额外判断长度，防止正则通过但值不完整的情况
                                        [
                                            'actionType' => 'stopPropagation',
                                            'expression' => '${!id_card || id_card.length !== 18}',
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
                                            'componentName' => 'patriarch_no',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.patriarch_no||CONCATENATE("S", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}',
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'patriarch_no',
                                            'expression' => '${!!event.data.responseResult.responseData.patriarch_no}',
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'patriarch_no',
                                            'expression' => '${!event.data.responseResult.responseData.patriarch_no}',
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
                                                'value' => '${event.data.responseResult.responseData.sex||3}',
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
                                                'value' => '${event.data.responseResult.responseData.nation||1}',
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
                                            'componentName' => 'childes',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.childes||[]}',
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        amis()->TextControl('patriarch_name', '真实姓名')->id('patriarch_name')->required(),
                        amis()->TextControl('patriarch_no', '系统编号')
                            ->value('${CONCATENATE("E", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*1000000000), 9, "0"))}')
                            ->readOnly(),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号码')
                            ->validations(['matchRegexp' => '/^1[3-9][\\d|*]{9}$/'])
                            ->validationErrors(['matchRegexp' => '请输入有效的中国大陆手机号码'])
                            ->required(),
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
                        ->options(Enum::nation())
                        ->value(1),
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
                                    amis()->HiddenControl('childes', false)->id('drawer_child_id'),
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
                                        ->columnsCount(2)
                                        ->placeholder('请输入条件搜索学生')
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
                                            // ✅ 优化2: 使用 tpl 渲染变量，替代 name + label 的非标准表单写法
                                            'body' => [
                                                [
                                                    'type' => 'tpl',
                                                    'label' => '年级',
                                                    'tpl' => '${grade.grade_name}',
                                                ],
                                                [
                                                    'type' => 'tpl',
                                                    'label' => '班级',
                                                    'tpl' => '${classes.classes_name}',
                                                ],
                                            ],
                                            'actions' => [
                                                [
                                                    'label' => '添加',
                                                    'actionType' => 'button',
                                                    'level' => 'link',
                                                    'icon' => 'iconfont icon-edap-tool-btn-add',
                                                    'confirmTitle' => '操作提示',
                                                    // ✅ 优化3: 确认文案中的变量渲染
                                                    'confirmText' => '是否添加 <b class="text-danger">${student.student_name}</b> 的关联关系?',
                                                    'onEvent' => [
                                                        'click' => [
                                                            'actions' => [
                                                                [
                                                                    'actionType' => 'custom',
                                                                    // ✅ 优化4: 精简 script 逻辑，增加注释，确保数据流清晰
                                                                    'script' => '
                                                                        // 1. 安全获取上下文数据
                                                                        let data = context?.data || event?.data || {};
                                                                        let currentItem = data.item || data;

                                                                        // 2. 数据完整性校验
                                                                        if (!currentItem || !currentItem.student_id) {
                                                                            doAction({
                                                                                actionType: "toast",
                                                                                args: { msgType: "error", msg: "数据获取失败" }
                                                                            });
                                                                            return;
                                                                        }

                                                                        // 3. 获取外层已有的关联列表（防止为空报错）
                                                                        let currentList = Array.isArray(data.childes) ? data.childes : [];

                                                                        // 4. 防重复校验
                                                                        let studentId = currentItem.student_id;
                                                                        let exists = currentList.some(function(item) {
                                                                            let id = item.student_id;
                                                                            return id === studentId;
                                                                        });

                                                                        if (exists) {
                                                                            doAction({
                                                                                actionType: "toast",
                                                                                args: { msgType: "warning", msg: "该学生已在关联列表中" }
                                                                            });
                                                                            return;
                                                                        }

                                                                        // 5. 追加新数据并精准更新外层组件
                                                                        //let newList = currentList.concat([currentItem]);

                                                                        newList = JSON.parse(JSON.stringify(currentList));
                                                                        newList.push(JSON.parse(JSON.stringify(currentItem)));

                                                                        // 6. ✅ 将拼接后的【完整新数组】赋值给目标组件
                                                                        doAction({
                                                                            actionType: "setValue",
                                                                            componentId: "form_cards_child_id",
                                                                            args: {
                                                                                value: {
                                                                                    childes: newList // 这里传入的是包含旧数据+新数据的完整数组
                                                                                }
                                                                            }
                                                                        });
                                                                        // 6.1 ✅ 【核心】追加完成后，强制刷新该组件
                                                                        //doAction({
                                                                        //    actionType: "reload",
                                                                        //    componentId: "form_cards_child_id"
                                                                        //});

                                                                        // 7. ✅ 同步更新另一个组件
                                                                        doAction({
                                                                            actionType: "setValue",
                                                                            componentId: "form_child_id",
                                                                            args: {
                                                                                value: newList
                                                                            }
                                                                        });
                                                                        // 8. ✅ 同步更新drawer另一个相同name组件
                                                                        doAction({
                                                                            actionType: "setValue",
                                                                            componentId: "drawer_child_id",
                                                                            args: {
                                                                                value: newList
                                                                            }
                                                                        });
                                                                        doAction({
                                                                            actionType: "toast",
                                                                            args: { msgType: "success", msg: "<b class=text-danger>" + currentItem.student.student_name + "</b> 添加成功" }
                                                                        });
                                                                        //doAction({
                                                                        //    actionType: "reload",
                                                                        //    componentId: "form_child_id"
                                                                        //});
                                                                    ',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ]),
                                ]),
                            ],
                        ]),
                ]),
                amis()->HiddenControl('childes', '关联学生')->id('form_child_id')->required(),
                amis()->Cards()
                    ->id('form_cards_child_id')
                    ->source('${childes}')
                    ->columnsCount(3)
                    ->placeholder('关联不能为空，至少添加一个学生')   // ✅ 空状态提示
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
                                                    let currentRow = event.data.item || event.data;
                                                    let childList = event.data.childes || [];
                                                    let newList = childList.filter(function(item) {
                                                        return item.student_id !== currentRow.student_id;
                                                    });
                                                    doAction({
                                                        actionType: "setValue",
                                                        componentId: "form_cards_child_id", // ✅ 关键：明确告诉 amis 更新哪个组件的数据
                                                        args: {
                                                            value: {
                                                                childes: newList
                                                            }
                                                        }
                                                    });
                                                    doAction({
                                                        actionType: "setValue",
                                                        componentId: "form_child_id",
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
                        amis()->TextControl('patriarch_no', '家长编号'),
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
