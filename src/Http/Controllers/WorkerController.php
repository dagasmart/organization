<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\WorkerService;
use DagaSmart\BizAdmin\Controllers\AdminController;
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
 * 基础-老师类
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
                amis()->TableColumn('id', 'ID')->sortable()->set('fixed','left'),
                amis()->TableColumn('teacher_name', '姓名')->sortable()->searchable()->set('fixed','left'),
                amis()->TableColumn('school_id', '所属学校')
                    ->searchable([
                        ['type'=>'tree-select', 'searchable'=>true, 'options'=>$this->service->getEnterpriseAll()],
                        ['type'=>'tree-select', 'searchable'=>true, 'options'=>$this->service->getEnterpriseAll()]
                    ])
                    //->breakpoint('*')
                    ->set('type','input-tag')
                    ->set('options',$this->service->getEnterpriseAll())
                    ->set('value','${school.school_id}')
                    ->set('fixed','left')
                    ->set('static', true),
                amis()->TableColumn('department_id', '部门')
                    ->searchable(['type'=>'tree-select', 'multiple'=>true, 'searchable'=>true, 'options'=>$this->service->getDepartmentAll()])
                    ->set('type', 'input-tag')
                    ->set('options', $this->service->getDepartmentAll())
                    ->set('value','${school.department_id}')
                    ->set('multiple', true)
                    ->set('width', 150)
                    ->set('static', true),
                amis()->TableColumn('job_id', '教师职务')
                    ->searchable(['type'=>'tree-select', 'multiple'=>true, 'searchable'=>true, 'options'=>$this->service->getJobAll()])
                    ->set('type', 'input-tag')
                    ->set('options', $this->service->getJobAll())
                    ->set('value','${school.job_id}')
                    ->set('multiple', true)
                    ->set('width', 150)
                    ->set('static', true),
                amis()->TableColumn('teacher_no','教师编码')->searchable()->sortable(),
                amis()->TableColumn('id_card','身份证号')->searchable()->sortable(),
                amis()->TableColumn('avatar', '老师照片')
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
                                                ->defaultImage('/admin-assets/no-error.svg')
                                                ->width('100%')
                                                ->height('100%'),
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                amis()->TableColumn('mobile', '联系电话')->searchable(),
//                amis()->TableColumn('area_id', '所属地区id')
//                    ->searchable(['name'=>'area_id','type'=>'input-city'])
//                    ->quickEdit(['type'=>'input-city','value'=>'${area_id}'])
//                    ->set('type','input-city')
//                    ->set('static',true)
//                    ->sortable(),
                amis()->TableColumn('alipay_user_id', '支付宝刷脸账号')->searchable(),
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
        return $this->baseForm()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->HiddenControl('id'),
                        amis()->TextControl('id_card', '身份证号')
                            ->required()
                            ->onEvent([
                                'blur' => [
                                    'actions' => [
                                        [
                                            'actionType' => 'ajax',
                                            'api' => [
                                                'method' => 'GET',
                                                'url' => admin_url('biz/school/teacher/${id_card||0}/check'),
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
                                            'componentName' => 'teacher_name',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.teacher_name||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'teacher_name',
                                            'expression' => '${!!event.data.responseResult.responseData.teacher_name}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'teacher_name',
                                            'expression' => '${!event.data.responseResult.responseData.teacher_name}'
                                        ],
                                        [
                                            'actionType'  => 'setValue',
                                            'componentName' => 'teacher_no',
                                            'args' => [
                                                'value' => '${event.data.responseResult.responseData.teacher_no||null}'
                                            ],
                                        ],
                                        [
                                            'actionType' => 'disabled',
                                            'componentName' => 'teacher_no',
                                            'expression' => '${!!event.data.responseResult.responseData.teacher_no}'
                                        ],
                                        [
                                            'actionType' => 'enabled',
                                            'componentName' => 'teacher_no',
                                            'expression' => '${!event.data.responseResult.responseData.teacher_no}'
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
                                        ]
                                    ]
                                ]
                            ]),
                        amis()->TextControl('teacher_name', '教师姓名')->id('teacher_name')->required(),
                        amis()->TextControl('teacher_no', '教师编码')->labelRemark('全国统一教师资格代码'),
                        amis()->TextControl('email', '常用邮箱'),
                        amis()->TextControl('mobile', '手机号')->required(),
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
            // 学校信息
            amis()->Tab()->title('学校信息')->body([
                amis()->ComboControl('combo', false)->items([
                    amis()->SelectControl('school_id', '学校${index+1}')
                        ->options($this->service->getEnterpriseAll())
                        ->searchable()
                        ->required(),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->options($this->service->getDepartmentAll())
                        ->onlyChildren()
                        ->onlyLeaf()
                        ->hideNodePathLabel()
                        ->searchable()
                        ->required(),
                    amis()->TreeSelectControl('job_id', '职务')
                        ->options($this->service->getJobAll())
                        ->menuTpl('<div class="flex justify-between"><span style="color: var(--button-link-default-font-color);">${label}</span><span class="ml-2 rounded p-1 text-xs text-gray-500 text-center w-full">${tag}</span></div>')
                        ->multiple(false)
                        ->maxTagCount(5)
                        ->onlyChildren()
                        ->onlyLeaf()
                        ->hideNodePathLabel()
                        ->searchable()
                        ->required(),
                    amis()->HiddenControl('teacher_id')->value('${id}'),
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
                amis()->InputCityControl('region_id', '所在地区')
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
                    amis()->TextControl('family_mobile','电话')->clearable(),
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
                        'script' => 'window.$owl.refreshAmisPage();'
                    ],
                ]
            ]
        ]);
    }

	public function detail(): Form
    {
		return $this->baseDetail()->mode('horizontal')->tabs([
            // 基本信息
            amis()->Tab()->title('基本信息')->body([
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->GroupControl()->direction('vertical')->body([
                        amis()->TextControl('teacher_name', '姓名'),
                        amis()->TextControl('teacher_sn', '教师编码'),
                        amis()->TextControl('id_card', '身份证号'),
                        amis()->TextControl('work_sn', '工号'),
                        amis()->RadiosControl('sex', '性别')
                            ->options(Enum::sex()),
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
                    amis()->SelectControl('nation_id', '民族')
                        ->options(Enum::nation()),
                    amis()->SelectControl('work_status', '工作状态')
                        ->options(Enum::WorkStatus),
                ]),
                amis()->Divider(),
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->SelectControl('full_teacher', '专职老师')
                        ->options(Enum::IsFull),
                    amis()->SelectControl('work_status', '工作状态')
                        ->options(Enum::WorkStatus),
                ]),
            ]),
            // 家庭情况
            amis()->Tab()->title('学校信息')->body([
                amis()->ComboControl('combo', false)->items([
                    amis()->SelectControl('school_id', '学校${index+1}')
                        ->options($this->service->getEnterpriseAll())->required(),
                    amis()->HiddenControl('teacher_id')->value('${id}'),
                    amis()->TreeSelectControl('department_id', '部门')
                        ->options($this->service->getDepartmentAll())
                        ->onlyChildren()
                        ->onlyLeaf()
                        ->hideNodePathLabel()
                        ->searchable()
                        ->required(),
                    amis()->TreeSelectControl('job_id', '职务')
                        ->options($this->service->getJobAll())
                        ->menuTpl('<div class="flex justify-between"><span style="color: var(--button-link-default-font-color);">${label}</span><span class="ml-2 rounded p-1 text-xs text-gray-500 text-center w-full">${tag}</span></div>')
                        ->multiple()
                        ->maxTagCount(5)
                        ->onlyChildren()
                        ->searchable()
                        ->required(),
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
                amis()->InputCityControl('region_id', '所在地区')
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
                amis()->GroupControl()->mode('horizontal')->body([
                    amis()->TextControl('address', '家庭住址'),
                    amis()->TextControl('email', '常用邮箱'),
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
                    amis()->TextControl('family_mobile','电话')->clearable(),
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

    public function SchoolTeacherCheck(): JsonResponse|JsonResource
    {
        $id_card = request()->id_card ?? null;
        $res = $this->service->SchoolTeacherCheck($id_card);
        return $this->response()->success($res);
    }

    public function importAction($api = null): DialogAction
    {
        return amis()->DialogAction()->label('一键导入')->icon('fa fa-upload')->dialog(
            amis()->Dialog()->title('一键导入-老师')->body([
                amis()->Action()
                    ->label('演示模板')
                    ->level('light')
                    ->icon('fa fa-wpforms')
                    ->className('float-right')
                    ->actionType('saveAs')
                    ->api(Storage::url('template/teacher.csv')),
                amis()->Divider()->color('transparent'),
                amis()->Form()->mode('normal')->api($api)->body([
                    amis()->FileControl()
                        ->name('file')
                        ->label('限制只能上传csv文件')
                        ->accept('.csv')
                        ->receiver('school/teacher/import')
                        //->startChunkApi('school/teacher/import')
                        //->chunkApi('school/teacher/import')
                        ->finishChunkApi('school/teacher/importChunk')
                        ->required()
                        ->drag()
                        ->onEvent([
                            'remove' => [
                                'actions' => [
                                    [
                                        'actionType' => 'ajax',
                                        'api' => [
                                            'url' => 'school/common/remove',
                                            'method' => 'post',
                                            'data' => [
                                                'path' => '${event.data.value}'
                                            ],
                                            'silent' => true
                                        ]
                                    ]
                                ]
                            ]
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
        $type     = request('t', 'uploads');
        $ext      = pathinfo($fileName, PATHINFO_EXTENSION);
        $path     = $type . '/' . $uploadId . '.' . $ext;
        $fullPath = storage_path('app/public/' . $path);
        make_dir(dirname($fullPath));
        for ($i = 0; $i < count($partList); $i++) {
            $partNumber = $partList[$i]['partNumber'];
            $eTag       = $partList[$i]['eTag'];
            $partPath = 'chunk/' . $uploadId . '/' . $partNumber;
            $partETag = md5(Storage::disk('public')->get($partPath));
            if ($eTag != $partETag) {
                return $this->response()->fail('分片上传失败');
            }
            file_put_contents($fullPath, Storage::disk('public')->get($partPath), FILE_APPEND);
        }
        clearstatcache();
        app('files')->deleteDirectory(storage_path('app/public/chunk/' . $uploadId));
        $this->readCsv($fullPath);
        return $this->response()->success(['value' => $path], '上传成功');
    }

    public function import(): JsonResponse|JsonResource
    {
        //try {
            // 验证文件是否存在且不为空
            if (request()->hasFile('file') && request()->file('file')->isValid()) {
                $file = request()->file('file');
                $filename = time() . $file->getClientOriginalName(); // 使用时间戳和原始名称作为文件名
                $path = $file->storeAs('files', $filename, 'public'); // 存储到 public 磁盘的 uploads 目录下
                foreach ($this->readCsv(public_storage_path($path)) as $i => $item) {
                    echo  $i . '行' . json_encode($item, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                }
                return $this->response()->success(['value' => $path], '文件上传成功！'); // 返回成功消息
            } else {
                return $this->response()->fail('文件上传失败！');
            }
        //} catch (\Exception $e) {
            //return $this->response()->fail('文件上传失败！');
        //}
    }


    public function readCsv($filePath)
    {

        $wg = new WaitGroup();
        $rows = SimpleExcelReader::create($filePath)->getRows()->toArray();
        foreach ($rows as $index => $row) {
                $wg->add(); // 增加等待计数
                Coroutine::run(function () use ($wg, $index, $row, &$results) {
                    try {
                        // 并发执行任务
                        dump($index . '_' . $row);
                    } finally {
                        $wg->done(); // 任务完成，减少等待计数
                    }
                });
        }

    }


}
