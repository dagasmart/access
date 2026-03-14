<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Enums\Enum;
use DagaSmart\Access\Services\AccessDispatchService;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Renderers\Panel;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * 基础-分发类
 *
 * @property AccessDispatchService $service
 */
class AccessDispatchController extends AdminController
{
    protected string $serviceName = AccessDispatchService::class;

    /**
     * 入口方法，根据请求类型返回列表数据、导出或页面
     */
    public function index()
    {
        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        if ($this->actionOfExport()) {
            return $this->export();
        }

        return $this->response()->success($this->page());
    }

    /**
     * 分发首页页面，左侧分类树，右侧分发列表
     */
    public function page(): Page
    {
        return amis()->Page()->body(
            amis()->Flex()->items([
                $this->tree(),
                $this->list(),
                //$this->chart(),
            ])
        );
    }

    /**
     * 左侧分类导航，用于筛选右侧列表
     */
    public function tree()
    {
        return amis()->Card()->className('w-1/5 mr-5 mb-0')->body([
            amis()
                ->Nav()
                ->style(['padding' => '10px 0'])
                ->links(AccessDispatchService::getNavList())
                ->stacked()
                ->searchable(),
        ]);
    }

    /**
     * 左侧分类导航，用于筛选右侧列表
     */
    public function chart()
    {
        return amis()->Card()->className('w-1/4 ml-5 mb-0')->body([
            amis()->Tabs()->tabsMode('line')->tabs([
                amis()->Tab()->title('异常排查')->icon('menu')->body([
                ]),
                amis()->Tab()->title('分析')->icon('menu')->body([
                ]),
            ]),
        ]);
    }

    /**
     * 分发列表页面，展示机构条目及操作按钮
     */
    public function list()
    {
        $crud = $this->baseCRUD()
            ->id('dispatch-crud') // 供左侧导航和刷新使用的 CRUD 容器 ID
            ->data(['module_enterprise_alias' => module_enterprise_alias()])
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton('drawer'),
                ...$this->baseHeaderToolBar(),
                // 当前分类说明，提示用户正在查看哪个分类下的分发
                amis()->Tpl()->tpl('<span class="text-secondary font-thin">${module_enterprise_alias}：</span><b>${enterprise_name || "全部"}</b>')->className('text-current')->align('right'),
            ])
            ->combineNum(3)
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('user_card', '用户/身份证号')
                    ->searchable(
                        amis()->FormControl()->body([
                            amis()->TextControl('user_name', '用户名')->placeholder('请输入查找的用户名')->clearable(),
                            amis()->TextControl('id_card', '身份证号')->placeholder('请输入查找的身份证号或后四位')->clearable(),
                        ])
                    )
                    ->set('type', 'tpl')
                    ->set('tpl', '${user.user_name}<h5 class="m-0 mt-1.5 text-secondary">${user.id_card}</h5>')
                    ->align('center')
                    ->width(100),

                amis()->TableColumn('user.user_avatar','照片')
                    ->set('type', 'avatar')
                    ->set('src', '${user.user_avatar}')
                    ->set('size', 60)
                    ->set('static', true)
                    ->set('onError','return true;')
                    ->set('onEvent', [
                        'click' => [
                            'actions' => [
                                [
                                    'actionType' => 'drawer',
                                    'drawer' => [
                                        'title' => false,
                                        'actions' => [],
                                        'closeOnEsc' => true, //esc键关闭
                                        'closeOnOutside' => true, //域外可关闭
                                        'showCloseButton' => false, //显示关闭
                                        'body' => [
                                            amis()->Image()
                                                ->src('${user.user_avatar}')
                                                ->defaultImage(url(admin_config('admin.default_image')))
                                                ->width('100%')
                                                ->height('100%'),
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),

                amis()->TableColumn('user.user_type', '类型')
                    ->searchable(
                        amis()->SelectControl('user_type')->options(Enum::user_type(false))->checkAll()->multiple()->clearable(),
                    )
                    ->set('type', 'input-tag')
                    ->set('options', Enum::user_type(false))
                    ->set('multiple', true)
                    ->set('static', true),

                amis()->TableColumn('device.facility_id', '${module_enterprise_alias}/设备信息')
                    ->searchable(amis()->FormControl()->body([
                        amis()->SelectControl('enterprise_id', '${module_enterprise_alias}')
                            ->options($this->service->getEnterpriseAll())
                            ->autoFill(['enterprise_name' => '${label}'])
                            ->searchable()
                            ->clearable(),
                        amis()->HiddenControl('enterprise_name', '${module_enterprise_alias}'),
                        amis()->TreeSelectControl('facility_id', '设施主体')
                            ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/options'))
                            ->options($this->service->getFacilityAll())
                            ->searchable()
                            ->disabledOn('${!enterprise_id}')
                            ->onlyLeaf()
                            ->clearable(),
                        amis()->TextControl('device_name', '设备名称')->placeholder('请输入查找的设备名称')->clearable(),
                        amis()->SelectControl('device_id', '设备编号')
                            ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/${facility_id||0}/device/access/brand/${device_brand||0}/options'))
                            ->options($this->service->getDeviceAll())
                            ->placeholder('请输入查找的设备编号')
                            ->clearValueOnSourceChange()
                            ->multiple(false)
                            ->searchable()
                            ->clearable(),
                    ]))
                    ->set('type', 'tpl')
                    ->set('tpl', '${device.rel.enterprise.enterprise_name}<h5 class="m-0 mt-1 text-secondary">设施：${device.rel.facility.level_name}</h5><h5 class="m-0 mt-1 text-secondary">名称：${device.device_name}</h5><h5 class="m-0 mt-1 text-secondary">编号：${device.device_sn}</h5>')
                    ->width(180),

                amis()->TableColumn('sort', '优先级')
                    ->sortable()
                    ->quickEdit(
                        amis()->NumberControl()->min(0)
                    ),

                amis()->TableColumn('state', '状态')
                    ->searchable(
                        amis()->SelectControl('state')->options(Enum::dispatch_state())->checkAll()->multiple()->clearable(),
                    )
                    ->type('status')
//                    ->set('type','mapping')
//                    ->set('map', ['*' => [
//                        'type' => 'status',
//                        'source' => Enum::dispatch_state()
//                    ]]),
                    ->map(array_column(Enum::dispatch_state(), 'icon', 'value'))
                    ->labelMap(array_column(Enum::dispatch_state(), 'label', 'value'))
                    ->set('align', 'center'),

                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->sortable()
                    ->width(100),

                //$this->rowActions('drawer')->fixed('right'),
                $this->rowActions([
                    $this->rowShowButton('drawer'),
                    $this->rowPublishButton('下发'),
                    $this->rowDeleteButton(),
                ])->fixed('right'),
            ]);

        return $this->baseList($crud);
    }

    /**
     * 分发表单页面，支持新增和编辑
     */
    public function form($isEdit = false): Form
    {
        return $this->baseForm()->data(['enterprise_id' => '${enterprise_id}'])->body([
            amis()->SelectControl('enterprise_id', module_enterprise_alias())
                ->options($this->service->getEnterpriseAll())
                ->value('${device.rel.enterprise_id}')
                ->searchable()
                ->clearable()
                ->disabled($isEdit)
                ->required(),
            amis()->TreeSelectControl('facility_id', '设施主体')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/options'))
                ->options($this->service->getFacilityAll())
                ->value('${device.rel.facility_id}')
                ->disabledOn('${!enterprise_id}')
                ->clearValueOnSourceChange()
                ->onlyLeaf()
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('device_brand', '设备品牌')
                ->source(admin_url('biz/enterprise/device/access/brand/options'))
                ->value('${device.device_brand}')
                ->placeholder('请选择品牌')
                ->disabledOn('${!facility_id}')
                ->clearValueOnSourceChange()
                ->searchable()
                ->clearable(),
            amis()->SelectControl('device_id', '分发设备')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/${facility_id||0}/device/access/brand/${device_brand||0}/options'))
                ->options($this->service->getDeviceAll())
                ->value('${device.id}')
                ->placeholder('请选择设备')
                ->disabledOn('${!facility_id}')
                ->clearValueOnSourceChange()
                ->showInvalidMatch()
                ->multiple()
                ->searchable()
                ->clearable()
                ->required(),
            amis()->RadiosControl('user_type', '用户类型')
                ->options(Enum::user_type(false))
                ->value('${user.user_type}')
                ->disabledOn('${!enterprise_id}')
                ->visibleOn('${!!enterprise_id}')
                ->required()
                ->static($isEdit),
            amis()->TreeSelectControl('grade_id', '年级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                ->disabledOn('${!enterprise_id}')
                ->searchable()
                ->onlyLeaf()
                ->required(!$isEdit)
                //->visible(!$isEdit)
                ->visibleOn('${(user_type === "student" || user_type === "patriarch") && !!enterprise_id}'),
            amis()->SelectControl('classes_id', '班级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                ->disabledOn('${!grade_id}')
                ->searchable()
                ->required()
                ->visible(!$isEdit)
                ->visibleOn('${(user_type === "student" || user_type === "patriarch") && !!grade_id}'),
            amis()->SelectControl('access_user_id', '用户')
                ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes/${classes_id||0}/user/${user_type||0}/all'))
                ->disabledOn('${!classes_id}')
//                ->selectMode('table')
//                ->columns([
//                    ['name' => 'label', 'label' => '姓名'],
//                    ['name' => 'id_card', 'label' => '身份证号'],
//                ])
                ->labelField('label_as')
                ->clearValueOnSourceChange()
                ->searchable()
                ->clearable()
                ->checkAll()
                ->multiple()
                ->required()
                ->visible(!$isEdit)
                ->visibleOn('${(user_type === "student" || user_type === "patriarch") && !!classes_id}'),
            amis()->StaticExactControl('user.user_name', '用户姓名')->visible($isEdit),
            amis()->StaticExactControl('user.id_card','身份证号')->visible($isEdit),
        ]);
    }

    /**
     * 编辑分发数据，编辑时需要将 metadata JSON 拆解成表单字段，否则无法回显
     */
    public function edit($id)
    {
        $this->isEdit = true;
        $data = $this->service->getEditData($id)->toArray();
        $data = $this->decodeMetadata($data);
        return $this->response()->success($data);
    }

    /**
     * 分发详情页面，展示分发所有字段信息
     */
    public function detail()
    {
        return $this->baseForm()->body([
            amis()->StaticExactControl('enterprise_name', module_enterprise_alias())->value('${device.rel.enterprise.enterprise_name}'),
            amis()->StaticExactControl('facility_name', '设施主体')->value('${device.rel.facility.level_name}'),
            amis()->StaticExactControl('device_brand', '设备品牌')->value('${device.device_brand}'),
            amis()->StaticExactControl('device_name', '设备名称')->value('${device.device_name}'),
            amis()->StaticExactControl('device_sn', '设备编号')->value('${device.device_sn}')->copyable(),
            amis()->TagControl('user_type', '用户类型')
                ->options(Enum::user_type(false))
                ->value('${user.user_type}')
                ->static(),
            amis()->TreeSelectControl('grade_id', '年级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                ->disabledOn('${!enterprise_id}')
                ->searchable()
                ->onlyLeaf()
                ->required()
                ->visibleOn('${(user_type === "student" || user_type === "patriarch") && !!enterprise_id}'),
            amis()->SelectControl('classes_id', '班级')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                ->disabledOn('${!grade_id}')
                ->searchable()
                ->required()
                ->visible()
                ->visibleOn('${(user_type === "student" || user_type === "patriarch") && !!grade_id}'),
            amis()->StaticExactControl('user.user_name', '用户姓名'),
            amis()->StaticExactControl('user.id_card','身份证号')->copyable(),
        ])
        ->static();
    }

    /**
     * 下发按钮
     * @param string $title
     * @return mixed
     */
    protected function rowPublishButton(string $title = ''): mixed
    {
        $dialog_title = $title . '至设备';
        $action = amis()->DialogAction()->dialog(
            amis()->Dialog()
                ->title($dialog_title)
                ->body([
                    amis()->Form()
                        ->wrapWithPanel(false)
                        ->api('put:/biz/access/dispatch/user/${id}/face/publish')
                        ->body([
                            amis()->HiddenControl('id'),
                            amis()->Page()->body('是否将 <b class="text-danger">${user.user_name}</b> 立即下发至设备 <b class="text-danger">${device.device_sn}</b> ?'),
                        ]),
                ])
                ->actions([
                    amis()->Button()->label('否，我再想想')->actionType('close'),
                    amis()->Button()->label('是，立即下发')->actionType('confirm')->primary()
                ])
                ->size('xs')
        );
        $action->label($title)->level('warning')->icon('download')->visible(admin_user()->administrator());
        return AdminPipeline::handle(AdminPipeline::PIPE_EDIT_ACTION, $action);
    }

    /**
     * 下发推送
     * @return JsonResponse|JsonResource
     */
    public function userFacePublish()
    {
        $this->service->userFacePublish();
        return $this->response()->success(true, '下发成功');
    }


    public function barChart(): Panel
    {
        return amis()->Panel()->className('w-full')->body([
            amis()->Chart()->height(250)->config([
                'backgroundColor' => '',
                'title'           => [
                    'text' => '任务汇总统计',
                    'subtext' => '统计图'
                ],
                'tooltip'         => ['trigger' => 'axis'],
                'legend'          => ['data' => ['最高气温', '最低气温']],
                'xAxis'           => [
                    'type'        => 'category',
                    'boundaryGap' => false,
                    'data'        => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                'yAxis'           => ['type' => 'value'],
                'grid'            => ['left' => '7%', 'right' => '3%', 'top' => 60, 'bottom' => 30,],
                'legend'          => ['data' => ['成功', '失败']],
                'series'          => [
                    [
                        'name'      => '成功',
                        'data'      => [10,2,30,4,50,16,7],
                        'type'      => 'line',
                        'areaStyle' => [],
                        'smooth'    => true,
                        'symbol'    => 'none',
                    ],
                    [
                        'name'      => '失败',
                        'data'      => [7,6,5,4,3,2,1],
                        'type'      => 'bar',
                        'areaStyle' => [],
                        'smooth'    => true,
                        'symbol'    => 'none',
                    ],
                ],
            ])
        ])->id('pie-chart-panel')->set('animations', [
            'enter' => [
                'delay'    => 0.1,
                'duration' => 0.5,
                'type'     => 'zoomIn',
            ],
        ]);
    }

}
