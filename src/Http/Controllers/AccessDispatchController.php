<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Access\Services\AccessDispatchService;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Organization\Enums\Enum;


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
     * 知识首页页面，左侧分类树，右侧知识列表
     */
    public function page()
    {
        return amis()->Page()->body(
            amis()->Flex()->items([
                $this->tree(),
                $this->list(),
            ])
        );
    }

    /**
     * 左侧分类导航，用于筛选右侧列表
     */
    public function tree()
    {
        return amis()->Card()->className('w-1/4 mr-5 mb-0')->body([
            amis()
                ->Nav()
                ->style(['padding' => '10px 0'])
                ->links(AccessDispatchService::getNavList())
                ->stacked()
                ->searchable(),
        ]);
    }

    /**
     * 知识列表页面，展示知识条目及操作按钮
     */
    public function list()
    {
        $module_enterprise_alias = module_enterprise_alias();

        $crud = $this->baseCRUD()
            ->id('dispatch-crud') // 供左侧导航和刷新使用的 CRUD 容器 ID
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton('drawer', 'lg'),
                ...$this->baseHeaderToolBar(),
                // 当前分类说明，提示用户正在查看哪个分类下的知识
                amis()->Tpl()->tpl("当前{$module_enterprise_alias}：<b>\${enterprise_name || '全部'}</b>")->align('right'),
            ])
            ->columns([
                amis()->TableColumn('user.user_name', '姓名')
                    ->copyable()
                    ->searchable(),

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

                amis()->TableColumn('device.device_name', '设备场景')
                    ->set('type', 'tpl')
                    ->set('tpl', '${device.rel.enterprise.enterprise_name}<h5 class="m-0 mt-1 text-secondary">设施：${device.rel.facility.level_name}</h5><h5 class="m-0 mt-1 text-secondary">名称：${device.device_name}</h5><h5 class="m-0 mt-1 text-secondary">编号：${device.device_sn}</h5>'),

                amis()->TableColumn('priority', '优先级')
                    ->sortable()
                    ->quickEdit(
                        amis()->NumberControl()->min(0)
                    ),

                amis()->TableColumn('status', '状态')
                    ->type('status')
                    ->map([1 => 'success', 0 => 'warning'])
                    ->labelMap([1 => '启用', 0 => '停用']),

                amis()->TableColumn('created_at', '创建时间')
                    ->type('datetime')
                    ->sortable(),

                $this->rowActions('drawer', 'lg'),
            ]);

        return $this->baseList($crud);
    }

    /**
     * 知识表单页面，支持新增和编辑
     */
    public function form($isEdit = false)
    {
        return $this->baseForm()->body($this->getFormBody());
    }

    /**
     * 编辑知识数据，编辑时需要将 metadata JSON 拆解成表单字段，否则无法回显
     */
    public function edit($id)
    {
        $this->isEdit = true;
        $data = $this->service->getEditData($id)->toArray();
        $data = $this->decodeMetadata($data);
        return $this->response()->success($data);
    }

    /**
     * 知识详情页面，展示知识所有字段信息
     */
    public function detail()
    {
        return $this->baseDetail()->body([
            amis()->TextControl('id', 'ID')->static(),
            amis()->TextControl('title', '标题')->static(),
            amis()->TextControl('category_code', '分类')->static(),
            amis()->TextControl('scene', '场景')->static(),
            amis()->TextareaControl('content', '内容')->static(),
            amis()->TextareaControl('metadata', '扩展信息')->static(),
            amis()->TextControl('priority', '优先级')->static(),
            amis()->TextControl('status', '状态')->static(),
            amis()->TextControl('created_at', '创建时间')->static(),
            amis()->TextControl('updated_at', '更新时间')->static(),
        ]);
    }

    /**
     * 解码 metadata JSON 并合并到数据数组中
     * metadata 结构来源于知识扩展字段，方便表单友好化展示和编辑
     */
    protected function decodeMetadata(array $data): array
    {
        if (!empty($data['metadata'])) {
            $meta = json_decode($data['metadata'], true) ?: [];
            $data['metadata_tags']       = $meta['tags'] ?? [];
            $data['metadata_ai_enabled'] = $meta['ai_enabled'] ?? 0;
            $data['metadata_confidence'] = $meta['confidence'] ?? 0.9;
            $data['metadata_max_tokens'] = $meta['max_tokens'] ?? 500;
            $data['metadata_source']     = $meta['source'] ?? '';
            $data['metadata_remark']     = $meta['remark'] ?? '';
        }
        return $data;
    }

    /**
     * 获取知识表单的表单元素配置
     */
    protected function getFormBody(): array
    {
        return [
            amis()->TextControl('title', '知识标题')
                ->required()
                ->maxLength(100),

            amis()->SelectControl('category_code', '分类')
                ->required()
                //->options(AccessDispatchService::getEnterpriseAll())
                ->searchable(),

            amis()->SelectControl('visibility', '可见性')
                ->required()
                //->options(AccessDispatchService::getEnterpriseAll())
                ->value('public')
                ->description('控制该知识的可见范围：公开（所有人）、私有（仅自己）'),


            amis()->WangEditor()->name('content')->label('知识内容')
                ->required()
                ->placeholder('请输入知识内容'),

            amis()->NumberControl('priority', '优先级')
                ->value(100)
                ->min(0),

            amis()->SwitchControl('status', '状态')
                ->value(1),

            // 以下 metadata 相关字段，最终会合并存回 metadata JSON
            amis()->Collapse()
                ->title('扩展信息')
                ->body([
                    amis()->SwitchControl('metadata_ai_enabled', 'AI 能力')->value(1)
                        ->description('开启后才会显示 AI 相关扩展设置（如可信度、最大 token、标签等）'),

                    amis()->SelectControl('metadata_tags', '标签')
                        //->options(KnowledgeMetaTags::options())
                        ->multiple(),

                    amis()->SelectControl('scene', '使用场景')
                        //->options(KnowledgeSceneService::getOptionList())
                        ->placeholder('请选择使用场景')
                        ->description('用于 AI 场景过滤'),

                    // 可信度越高，AI 回复越详细（token 越大）
                    amis()->RangeControl('metadata_confidence', '可信度')
                        ->min(0)
                        ->max(1)
                        ->step(0.01)
                        ->value(0.9)
                        ->tooltipVisible(true)
                        ->description('可信度（0～1）：表示模型认为内容正确/可靠的概率。越高越可信（建议 > 0.8）')
                        ->onEvent([
                            'change' => [
                                'actions' => [
                                    [
                                        'componentId' => 'u:391d61286128',
                                        'actionType' => 'setValue',
                                        'args' => [
                                            'value' => '${event.data.value * 1000}',
                                        ],
                                    ],
                                ],
                            ]
                        ])
                        ->unit('')
                        ->tooltipPlacement('auto')
                        ->showInput('')
                        ->marks([]),

                    // AI 最大 token 控制，配合可信度联动，决定 AI 回答最大长度
                    amis()->NumberControl('metadata_max_tokens', 'AI 最大 token')
                        ->id('u:391d61286128')
                        ->value(900)
                        ->description('AI 最大 token：控制 AI 回答最大长度')
                        ->disabled(true),

                    amis()->SelectControl('metadata_source', '来源')
                        //->options(KnowledgeSource::options())
                        ->placeholder('请选择来源'),

                    amis()->TextControl('metadata_remark', '内部备注'),
                ])
                ->collapsed(false),
        ];
    }
}
