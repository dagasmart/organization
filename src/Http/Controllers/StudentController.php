<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * 基础-学生表
 *
 * @property StudentService $service
 */
class StudentController extends AdminController
{
    protected string $serviceName = StudentService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable()
            ->headerToolbar([
                $this->createButton('dialog'),
                ...$this->baseHeaderToolBar(),
                $this->importAction(admin_url('student/import')),
                $this->exportAction(),
                $this->classesAction(),
            ])
            ->filter($this->baseFilter()->body([
                amis()->SelectControl('enterprise_id', module_enterprise_alias())
                    ->options($this->service->getEnterpriseAll())
                    ->placeholder('请选择机构...')
                    ->searchable()
                    ->clearable(),
                amis()->SelectControl('grade_id', '年级')
                    ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                    ->selectMode('group')
                    ->searchable()
                    ->clearable(),
                amis()->SelectControl('classes_id', '班级')
                    ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                    ->selectMode('group')
                    ->searchable()
                    ->clearable(),
                amis()->Divider(),
                amis()->TextControl('student_name', '学生姓名')
                    ->clearable()
                    ->placeholder('请输入学生姓名')
                    ->size('sm'),
                amis()->TextControl('id_card', '身份证号')
                    ->clearable()
                    ->placeholder('请输入学生身份证号')
                    ->size('md'),
            ]))
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable()->fixed('left'),
                amis()->TableColumn('student_name', '姓名')->searchable()->fixed('left'),
                amis()->TableColumn('student_code', '国网学籍')->searchable(),
                amis()->TableColumn('rel.enterprise.enterprise_name', module_enterprise_alias())
                    ->searchable(
                        amis()->FormControl()->body([
                            amis()->SelectControl('enterprise_id', module_enterprise_alias())
                                ->options($this->service->getEnterpriseAll())
                                ->searchable()
                                ->clearable(),
                            amis()->SelectControl('grade_id', '年级')
                                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                                ->selectMode('group')
                                ->searchable()
                                ->clearable(),
                            amis()->SelectControl('classes_id', '班级')
                                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                                ->selectMode('group')
                                ->searchable()
                                ->clearable(),
                        ])
                    )
                    ->width(200),
                amis()->TableColumn('grade_classes', '年级/班级')
                    ->tpl('${rel.grade.grade_name} / ${rel.classes.classes_name}')
                    ->width(150),
                // amis()->TableColumn('rel.grade.grade_name', '年级')->width(100),
                // amis()->TableColumn('rel.classes.classes_name', '班级')->width(100),
                amis()->TableColumn('avatar', '学生照片')
                    ->set('src', '${avatar}')
                    ->set('type', 'avatar')
                    ->set('fit', 'cover')
                    ->set('size', 'small')
                    ->set('onError', 'return true;')
                    ->set('onEvent', [
                        'click' => [
                            'actions' => [
                                [
                                    'actionType' => 'drawer',
                                    'drawer' => [
                                        'title' => false,
                                        'actions' => [],
                                        'closeOnEsc' => true, // esc键关闭
                                        'closeOnOutside' => true, // 域外可关闭
                                        'showCloseButton' => false, // 显示关闭
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
                amis()->TableColumn('sex', '性别')
                    ->searchable([
                        'name' => 'sex',
                        'type' => 'checkboxes',
                        'options' => $this->service->getModel()->sexOption(),
                    ])
                    ->set('type', 'checkboxes')
                    ->set('options', $this->service->getModel()->sexOption())
                    ->set('static', true),
                amis()->TableColumn('rel.state', '状态')
                    ->set('type', 'mapping')
                    ->set('map', Enum::student_state())
                    ->set('static', true),
                amis()->TableColumn('id_card', '身份证号')->searchable(),
                amis()->TableColumn('mobile', '电话')->searchable(),
                amis()->TableColumn('updated_at', admin_trans('admin.updated_at'))->type('datetime')->sortable(),
                $this->rowActions('dialog')
                    ->set('align', 'center')
                    ->set('fixed', 'right')
                    ->set('width', 150),
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->id('student_form_id')->data(['isEdit' => $isEdit, 'student_code_param_number' => '${student_code_param.number}'])->mode('horizontal')->tabs([

            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->Flex()->items([
                        amis()->GroupControl()->className('w-5/6')->direction('vertical')->body([
                            amis()->HiddenControl('id', 'ID')->disabled($isEdit),
                            amis()->InputGroupControl('id_card', '身份证号')->required()->body([
                                amis()->TextControl('id_card', '身份证号')
                                    ->disabled($isEdit)
                                    ->required()
                                    ->validateOnChange()
                                    ->validations([
                                        'matchRegexp' => '/^[\\d|*]{17}[\\dXx]$/i',
                                    ])
                                    ->validationErrors([
                                        'matchRegexp' => '请输入有效的身份证号码',
                                    ])
                                    ->onEvent([
                                        'change' => [
                                            // ✅ 新增：防抖，避免输入过程中频繁请求
                                            'debounce' => 300,
                                            'actions' => [
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
                                                    'actionType' => 'loading',
                                                    'args' => [
                                                        'isLoading' => true,
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'ajax',
                                                    'api' => [
                                                        'method' => 'GET',
                                                        'url' => admin_url('biz/enterprise/student/${id_card||0}/check'),
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
                                                    'componentName' => 'student_name',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.student_name||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'student_name',
                                                    'expression' => '${!!event.data.responseData.student_name}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'student_name',
                                                    'expression' => '${!event.data.responseData.student_name}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'student_code_param_type',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.student_code_param.type||"G"}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'student_code_param_type',
                                                    'expression' => '${!!event.data.responseData.student_code_param.type}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'student_code_param_type',
                                                    'expression' => '${!event.data.responseData.student_code_param.type}',
                                                ],
                                                // 情况1：接口返回了 enc → 直接解码
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'student_code_param_number',
                                                    'expression' => '${!!event.data.responseData.student_code_param}',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.student_code_param.enc | base64Decode}',
                                                    ],
                                                ],
                                                // 情况2：接口没返回 enc → 用 id_card 编码后再解码（等价于直接用 id_card）
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'student_code_param_number',
                                                    'expression' => '${!event.data.responseData.student_code_param && !!id_card}',
                                                    'args' => [
                                                        // 💡 base64Encode 再 base64Decode = 原值，直接用 id_card 即可
                                                        'value' => '${id_card}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'student_code_param_number',
                                                    'expression' => '${!!event.data.responseData.student_code_param.number}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'student_code_param_number',
                                                    'expression' => '${!event.data.responseData.student_code_param.number}',
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentId' => 'form_student_code_random',
                                                    'expression' => '${!!event.data.responseData.student_code_param  && !!id_card}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'avatar',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.avatar||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'avatar',
                                                    'expression' => '${!!event.data.responseData.avatar}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'avatar',
                                                    'expression' => '${!event.data.responseData.avatar}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'sex',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.sex||3}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'sex',
                                                    'expression' => '${!!event.data.responseData.sex}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'sex',
                                                    'expression' => '${!event.data.responseData.sex}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'nation',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.nation||1}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'nation',
                                                    'expression' => '${!!event.data.responseData.nation}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'nation',
                                                    'expression' => '${!event.data.responseData.nation}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'email',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.email||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'email',
                                                    'expression' => '${!!event.data.responseData.email}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'email',
                                                    'expression' => '${!event.data.responseData.email}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'mobile',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.mobile||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'mobile',
                                                    'expression' => '${!!event.data.responseData.mobile}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'mobile',
                                                    'expression' => '${!event.data.responseData.mobile}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'region_id',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.region_id||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'region_id',
                                                    'expression' => '${!!event.data.responseData.region_id}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'region_id',
                                                    'expression' => '${!event.data.responseData.region_id}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'address',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.address||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'address',
                                                    'expression' => '${!!event.data.responseData.address}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'address',
                                                    'expression' => '${!event.data.responseData.address}',
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'region_info',
                                                    'args' => [
                                                        'value' => '${event.data.responseData.region_info||null}',
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
                                                        'value' => '${event.data.responseData.family||null}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'disabled',
                                                    'componentName' => 'family',
                                                    'expression' => '${!!event.data.responseData.family}',
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'family',
                                                    'expression' => '${!event.data.responseData.family}',
                                                ],
                                            ],
                                        ],
                                    ]),
                                amis()->Button()
                                    ->label(PHP_EOL)
                                    ->icon('edit')
                                    ->visible($isEdit)
                                    ->tooltip('编辑')
                                    ->tooltipPlacement('right')->onEvent([
                                        'click' => [
                                            'actions' => [
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'id_card',
                                                    'args' => [
                                                        'disabledOn' => false,
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'id_card',
                                                    'args' => [
                                                        'value' => '${id_card_enc | base64Decode}',
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'student_code_param_type',
                                                    'args' => [
                                                        'disabledOn' => false,
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'enabled',
                                                    'componentName' => 'student_code_param_number',
                                                    'args' => [
                                                        'disabledOn' => false,
                                                    ],
                                                ],
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'student_code_param_number',
                                                    'args' => [
                                                        'value' => '${student_code_param.enc | base64Decode}',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]),
                            ]),
                            amis()->TextControl('student_name', '姓名')->required(),
                            amis()->InputGroupControl('student_code_param', '国网学籍')->mode('horizontal')->body([
                                amis()->SelectControl('student_code_param_type', '类型')
                                    ->options([
                                        ['label' => 'G', 'value' => 'G'],
                                        ['label' => 'J', 'value' => 'J'],
                                        ['label' => 'L', 'value' => 'L'],
                                    ])->value('${student_code_param.type || "G"}')
                                    ->disabled($isEdit),
                                amis()->TextControl('student_code_param_number', '学籍号')
                                    ->validations([
                                        'isRequired' => '${student_code_param_type !=== "G"}',
                                        'matchRegexp' => '/^[\\d|*]{17}[\\dXx]$/i',
                                    ])
                                    ->validationErrors([
                                        'isRequired' => '学籍号为必填项',
                                        'matchRegexp' => '格式错误：必须为18位数字',
                                    ])
                                    ->validateOnChange()
                                    ->disabledOn('${isEdit || student_code_param_type === "G"}')
                                    ->value('${student_code_param_type === "G" ? id_card : (student_code_param_number ?? null)}'),
                                amis()->VanillaAction()
                                    ->id('form_student_code_random')
                                    ->icon('iconfont icon-cdnrefresh')
                                    ->hiddenOn('${student_code_param_type === "G"}')
                                    ->tooltip('随机生成')
                                    ->tooltipPlacement('right')
                                    ->onEvent([
                                        'click' => [
                                            'actions' => [
                                                [
                                                    'actionType' => 'setValue',
                                                    'componentName' => 'student_code_param_number',
                                                    'args' => [
                                                        'value' => '${CONCATENATE("", DATETOSTR(TODAY(), "YYYYMMDDHHmmss"),PADSTART(INT(RAND()*10000), 4, "0"))}',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]),
                            ]),
                            amis()->HiddenControl('student_code', '国网学籍')
                                ->value('${student_code_param_type}${student_code_param_number}'),
                            amis()->SelectControl('enterprise_id', '机构')
                                ->options($this->service->getEnterpriseAll())
                                ->value('${rel.enterprise.id}')
                                ->searchable()
                                ->clearable()
                                ->required(),
                            amis()->SelectControl('grade_id', '年级')
                                // ->options($this->service->getGradeAll())
                                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                                ->selectMode('group')
                                ->value('${rel.grade.id}')
                                ->searchable()
                                ->clearable()
                                ->disabledOn('${!enterprise_id}')
                                ->required(),
                            amis()->SelectControl('classes_id', '班级')
                                // ->options($this->service->getClassesAll())
                                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                                ->selectMode('group')
                                ->value('${rel.classes.id}')
                                ->searchable()
                                ->clearable()
                                ->disabledOn('${!grade_id}')
                                ->showInvalidMatch()
                                ->required(),
                        ]),
                        amis()->GroupControl()->className('ml-5')->direction('vertical')->body([
                            amis()->ImageControl('avatar', false)
                                ->thumbRatio('1:1')
                                ->thumbMode('cover h-full rounded-md overflow-hidden')
                                ->className(['overflow-hidden' => true, 'h-full' => true])
                                ->imageClassName([
                                    'w-60' => true,
                                    'h-80' => true,
                                    'overflow-hidden' => true,
                                ])
                                ->fixedSize()
                                ->fixedSizeClassName([
                                    'w-60' => true,
                                    'h-80' => true,
                                    'overflow-hidden' => true,
                                ])
                                ->crop([
                                    'aspectRatio' => '0.75',
                                ]),
                        ]),
                    ]),
                ]),
                amis()->Divider()->color('var(--colors-brand-6)'),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->SelectControl('sex', '性别')
                        ->options(Enum::sex())
                        ->value(3)
                        ->required(),
                    amis()->SelectControl('nation', '民族')
                        ->options(Enum::nation())
                        ->value(1)
                        ->required(),
                    amis()->SelectControl('state', '状态')
                        ->options(Enum::StudentState)
                        ->value('${rel.state || (enterprise_id ? 1 : null)}')
                        ->disabledOn('${!enterprise_id}')
                        ->required(),
                ]),
                amis()->TextareaControl('remark', '备注'),
            ]),

            // 家庭情况
            amis()->Tab()->title('家庭情况')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('email', '常用邮箱'),
                    amis()->TextControl('mobile', '常用手机'),
                ]),
                amis()->InputCityControl('region_id', '所在地区')
                    ->searchable()
                    ->extractValue(false)
                    ->value(admin_region_code())
                    ->onEvent([
                        'init' => [
                            'actions' => [
                                [
                                    'actionType' => 'setValue',
                                    'componentId' => 'form_region_info',
                                    'args' => [
                                        'city' => [
                                            'province' => '${region_id.province}',
                                            'city' => '${region_id.city}',
                                            'district' => '${region_id.district}',
                                        ],
                                    ],
                                ],
                            ],
                        ],
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
                amis()->TextControl('address', '家庭住址')->visibleOn('${!!region_info.code}')
                    ->desc('${region_info.province} ${region_info.city} ${region_info.district} ${address}'),
                amis()->HiddenControl('address_info', '详细地址')
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
            //
            //            // 基本信息
            //            amis()->Tab()->title(admin_trans('admin.code_generators.base_info'))->body([
            //                amis()->GroupControl()->mode('normal')->body([
            //                    amis()->TextControl('is_pay', '缴费状态 1已缴费 2未缴费'),
            //                    amis()->TextControl('pay_status', '支付状态：1=正常，2=异常(有待支付订单) , 3禁用拉黑'),
            //                    amis()->TextControl('pay_status_clock', '黑名单状态锁，防止定时任务刷新状态，0未锁定，1锁定'),
            //                    amis()->TextControl('pay_status_clock_time', '锁定时间'),
            //                    amis()->TextControl('enjoy_sponsor', '是否享受资助 1是 2否'),
            //                    amis()->TextControl('sponsor_money', '资助金额'),
            //                    amis()->TextControl('send_sponsor_type', '发放方式'),
            //                    amis()->TextControl('send_sponsor_time', '发放时间'),
            //                    amis()->TextControl('school_face_pass_status', '校园一脸通行开通状态 OPEN 开通 CLOSE关闭'),
            //                    amis()->TextControl('school_face_payment_status', '校园一脸通行刷脸支付开通状态 OPEN开通 CLOSE关闭'),
            //                    amis()->TextControl('school_face_data', '校园一脸通行开通返回的数据'),
            //                    amis()->TextControl('end_time', '服务费截止时间'),
            //                    amis()->TextControl('ali_user_id', '刷脸用户id'),
            //                    amis()->TextControl('alifacepaystatus', '开通刷脸支付 0未开通，1已开通'),
            //                    amis()->TextControl('alifacepayopertime', '开通刷脸支付时间'),
            //                    amis()->TextControl('day_maxpay', '日消费限额'),
            //                    amis()->TextControl('non_payment_num', '未支付订单数量'),
            //                ]),
            //            ]),

        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->mode('horizontal')->tabs([

            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->StaticExactControl('id', 'ID')->copyable(),
                        amis()->StaticExactControl('id_card', '身份证号')->copyable(['content' => '${id_card_enc|base64Decode}']),
                        amis()->TextControl('student_name', '姓名')->required(),
                        amis()->TextControl('student_code', '国网学籍'),
                        amis()->TextControl('enterprise', is_school_module() ? '学校' : '机构')->value('${rel.enterprise.enterprise_name}'),
                        amis()->TextControl('grade_classes', '年级/班级')
                            ->value('${rel.grade.grade_name} / ${rel.classes.classes_name}'),
                    ]),
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->ImageControl('avatar')
                            ->thumbRatio('1:1')
                            ->thumbMode('cover h-full rounded-md overflow-hidden')
                            ->className(['overflow-hidden' => true, 'h-full' => true])
                            ->imageClassName([
                                'w-60' => true,
                                'h-80' => true,
                                'overflow-hidden' => true,
                            ])
                            ->fixedSize()
                            ->fixedSizeClassName([
                                'w-60' => true,
                                'h-80' => true,
                                'overflow-hidden' => true,
                            ])
                            ->crop([
                                'aspectRatio' => '0.75',
                            ]),
                    ]),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->SelectControl('sex', '性别')
                        ->options(Enum::sex())
                        ->value(3)
                        ->required(),
                    amis()->SelectControl('nation', '民族')
                        ->options(Enum::nation())
                        ->value(1)
                        ->required(),
                    amis()->SelectControl('state', '状态')
                        ->options(Enum::StudentState)
                        ->value('${rel.state}')
                        ->required(),
                ]),
            ]),
            // 家庭情况
            amis()->Tab()->title('家庭情况')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('email', '常用邮箱'),
                    amis()->TextControl('mobile', '常用手机'),
                ]),
                amis()->InputCityControl('region_id', '所在地区')
                    ->searchable()
                    ->extractValue(false)
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
                amis()->TextControl('address_info', '详细地址')
                    ->value('${region_info.province} ${region_info.city} ${region_info.district} ${address}'),
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

    public function importAction($api = null): DialogAction
    {
        return amis()->DialogAction()->label('一键导入')->icon('upload')->dialog(
            amis()->Dialog()->title('一键导入-学生')->body([
                amis()->Action()
                    ->label('演示模板')
                    ->level('light')
                    ->icon('iconfont icon-doc')
                    ->className('float-right')
                    ->actionType('saveAs')
                    ->api(Storage::url('template/student.csv')),
                amis()->Divider()->color('transparent'),
                amis()->Form()->mode('normal')->api($api)->body([
                    amis()->FileControl()
                        ->name('file')
                        ->label('限制只能上传csv文件')
                        ->accept('.csv')
                        ->receiver('enterprise/student/import')
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

    public function import(): JsonResponse|JsonResource
    {
        // 验证文件是否存在且不为空
        if (request()->hasFile('file') && request()->file('file')->isValid()) {
            $file = request()->file('file');
            $filename = str_replace('.', '', microtime(true)).$file->getClientOriginalName(); // 使用时间戳和原始名称作为文件名
            $path = $file->storeAs('files', $filename, 'public'); // 存储到 public 磁盘的 uploads 目录下

            return $this->response()->success(['value' => $path], '文件上传成功！'); // 返回成功消息
        } else {
            return $this->response()->fail('文件上传失败！');
        }
    }

    /**
     * 班级管理-弹窗
     */
    public function classesAction(): DialogAction
    {
        $form = $this->baseForm()->api(admin_url('biz/enterprise/classes'))->data([
            'enabled' => true,
            'sort' => 0,
        ])->body([
            amis()->StaticExactControl('id', 'ID')->visibleOn('${id}'),
            amis()->SelectControl('enterprise_id', '机构')
                ->options($this->service->getEnterpriseAll())
                ->value('${rel.enterprise_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->SelectControl('grade_id', '年级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                ->value('${rel.grade_id}')
                ->selectMode('group')
                ->searchable()
                ->clearable()
                ->disabledOn('${!enterprise_id}')
                ->required(),
            amis()->TextControl('classes_name', '班级')
                ->maxLength(50)
                ->clearable()
                ->disabledOn('${!grade_id}')
                ->required(),
            amis()->NumberControl('sort', '排序')->size('xs'),
            amis()->SwitchControl('status', '状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true),
        ]);

        $createButton = amis()->DialogAction()
            ->dialog(amis()->Dialog()->title(__('admin.create'))->body($form))
            ->label(__('admin.create'))
            ->icon('iconfont icon-edap-tool-btn-add')
            ->level('primary');

        $editForm = (clone $form)
            ->api('put:biz/enterprise/classes/${id}');
        // ->initApi('biz/enterprise/classes/${id}/edit?_action=getData');

        $editButton = amis()->DialogAction()
            ->dialog(amis()->Dialog()->title(__('admin.edit'))->body($editForm))
            ->label(__('admin.edit'))
            ->icon('pencil')
            ->level('link');

        $deleteButton = amis()->DialogAction()
            ->label(__('admin.delete'))
            ->className('text-danger')
            ->icon('close')
            ->level('link')
            ->dialog(
                amis()
                    ->Dialog()
                    ->title()
                    ->className('py-2')
                    ->actions([
                        amis()->Action()->actionType('cancel')->label(admin_trans('admin.cancel')),
                        amis()->Action()->actionType('submit')->label(admin_trans('admin.delete'))->level('danger'),
                    ])
                    ->body([
                        amis()->Form()->wrapWithPanel(false)->api('delete:biz/enterprise/classes/${id}')->body([
                            amis()->Tpl()->className('py-2')->tpl(admin_trans('admin.confirm_delete')),
                        ]),
                    ])
            );

        $bulkDeleteButton = amis()->DialogAction()
            ->label(__('admin.delete'))
            ->className('text-danger border border-dashed border-danger')
            ->icon('iconfont icon-trash-alt')
            ->dialog(
                amis()
                    ->Dialog()
                    ->title(admin_trans('admin.delete'))
                    ->className('py-2')
                    ->actions([
                        amis()->Action()->actionType('cancel')->label(admin_trans('admin.cancel')),
                        amis()->Action()->actionType('submit')->label(admin_trans('admin.delete'))->level('danger'),
                    ])
                    ->body([
                        amis()->Form()->wrapWithPanel(false)->api('delete:biz/enterprise/classes/${ids}')->body([
                            amis()->Tpl()->className('py-2')->tpl(admin_trans('admin.confirm_delete')),
                        ]),
                    ])
            );

        return amis()->DialogAction()->label('班级管理')->icon('iconfont icon-standard_bts')->dialog(
            amis()->Dialog()->title('班级管理')->size('md')->actions([])->body(
                amis()->CRUDTable()
                    ->perPage(10)
                    ->affixHeader(false)
                    ->filterTogglable()
                    ->filterDefaultVisible(false)
                    ->bulkActions([$bulkDeleteButton])
                    ->perPageAvailable([10, 20, 30, 50, 100, 200])
                    ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
                    ->api(admin_url('biz/enterprise/classes?_action=getData'))
                    ->headerToolbar([
                        $createButton,
                        'bulkActions',
                        amis('reload')->icon('iconfont icon-bcmrefresh')->set('align', 'right'),
                        amis('filter-toggler')->set('align', 'right'),
                    ])
                    ->filter(
                        $this->baseFilter()->body([
                            amis()->SelectControl('enterprise_id', '机构')
                                ->options($this->service->getEnterpriseAll())
                                ->searchable()
                                ->clearable()
                                ->size('md'),
                            amis()->SelectControl('grade_id', '年级')
                                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                                ->selectMode('group')
                                ->searchable()
                                ->clearable()
                                ->size('sm'),
                            amis()->CheckboxesControl('status', '状态')
                                ->options([
                                    '1' => '开启',
                                    '0' => '禁用',
                                ]),
                        ])
                    )
                    ->columns([
                        amis()->TableColumn('id', 'ID')->sortable(),
                        amis()->TableColumn('classes_name', '班级'),
                        amis()->TableColumn('rel.grade.grade_name', '年级'),
                        amis()->TableColumn('rel.enterprise.enterprise_name', module_enterprise_alias()),
                        amis()->TableColumn('status', '状态')
                            ->set('type', 'status')
                            ->set('options', ['1' => '开启', '0' => '禁用']),
                        amis()->Operation()->label(__('admin.actions'))->buttons([
                            $editButton,
                            $deleteButton,
                        ])->set('width', 150),
                    ])
            )
        );
    }

    public function search()
    {
        return $this->service->search();
    }

    /**
     * 检查身份证并获取学生信息
     */
    public function enterpriseStudentCheck(): JsonResponse|JsonResource
    {
        $id_card = request()->id_card ?? null;

        identifyByIdCard($id_card);

        $res = $this->service->enterpriseStudentCheck($id_card);

        return $this->response()->success($res);
    }
}
