<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Enums\Enum;
use DagaSmart\Access\Services\AccessUserService;
use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;

// use PhpMqtt\Client\Facades\MQTT;

/**
 * 基础-门禁用户类
 *
 * @property AccessUserService $service
 */
class AccessUserController extends AdminController
{
    protected string $serviceName = AccessUserService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton('dialog'),
                ...$this->baseHeaderToolBar(),
                $this->importAction('put:biz/access/user/import'),
            ])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID')
                    ->sortable()
                    ->set('fixed', 'left'),
                amis()->TableColumn('user_card', '用户')
                    ->searchable(amis()->FormControl()->body([
                        amis()->TextControl('user_name', '用户名')->placeholder('请输入查找的用户名')->clearable(),
                        amis()->TextControl('id_card', '身份证号')->placeholder('请输入查找的身份证号')->clearable(),
                    ]))
                    ->set('type', 'tpl')
                    ->set('tpl', '${user_name}<h5 class="m-0 mt-1.5 text-secondary">${id_card}</h5><h5 class="m-0 mt-1.5 text-secondary">${user_id}</h5>')
                    ->align('center')
                    ->width(100),
                amis()->TableColumn('rel.enterprise.enterprise_name', module_enterprise_alias().'信息')
                    ->searchable([
                        'name' => 'enterprise_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getEnterpriseAll(),
                    ])
                    ->set('type', 'tpl')
                    ->set('tpl', '${rel.enterprise.enterprise_name||rel.enterprise_name}<h5 class="m-0 mt-1 text-secondary">${rel.grade.grade_name || rel.department.department_name}</h5><h5 class="m-0 mt-1.5 text-secondary">${rel.classes.classes_name}</h5>')
                    ->width(200),
                amis()->TableColumn('avatar', '照片')
                    ->set('type', 'avatar')
                    ->set('src', '${avatar}')
                    ->set('size', 60)
                    ->set('static', true)
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
                amis()->TableColumn('user_type', '用户类型')
                    ->searchable(
                        amis()->SelectControl('user_type')->options(Enum::user_type())->checkAll()->multiple()->clearable(),
                    )
                    ->set('type', 'select')
                    ->set('options', Enum::user_type())
                    ->set('static', true),
                amis()->TableColumn('open_type', '用户类型')
                    ->searchable(
                        amis()->SelectControl('open_type')->options(Enum::open_type())->checkAll()->multiple()->clearable(),
                    )
                    ->set('type', 'input-tag')
                    ->set('options', Enum::open_type())
                    ->set('multiple', true)
                    ->set('static', true),
                amis()->TableColumn('state', '状态')
                    ->set('type', 'switch')
                    ->set('onText', '正常')
                    ->set('offText', '禁用'),
                amis()->TableColumn('sort', '排序')->sortable(),
                amis()->TableColumn('updated_at', '更新时间')
                    ->searchable(
                        amis()->DateRangeControl('updated_at')->valueFormat('YYYY-MM-DD HH:mm:ss'),
                    )
                    ->type('datetime')
                    ->sortable()
                    ->width(150),
                $this->rowActions([
                    amis()->Operation()->label(admin_trans('admin.actions'))->buttons([
                        $this->rowShowButton(true),
                        $this->rowEditButton(true),
                        $this->rowDeleteButton(),
                        // $this->rowSetAction('drawer', 'auto'),
                        $this->rowSendAction('drawer', 'lg'),
                    ]),
                ])
                    ->set('align', 'center')
                    ->set('fixed', 'right')
                    ->set('width', 150),
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->Tabs()->tabsMode('line')->tabs([
                amis()->Tab()->title('用户信息')->icon('menu')->body([
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()
                            ->RadiosControl('user_type', '用户类型')
                            ->options(Enum::user_type(['student', 'patriarch', 'worker']))
                            ->value('visitor')
                            ->disabled($isEdit)
                            ->visible(! $isEdit),
                    ]),
                    amis()->Divider()->lineStyle('dashed')->visible(! $isEdit),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()->GroupControl()->direction('vertical')->body([
                            amis()->StaticExactControl('id', 'ID')->visibleOn('${id}')->copyable(),
                            amis()->TagControl('user_type', '用户类型')
                                ->options(Enum::user_type())
                                ->static('${user_type !== "visitor"}')
                                ->disabledOn('${user_type !== "visitor"}')
                                ->visible($isEdit),
                            amis()->InputGroupControl(false, '用户姓名')
                                ->body([
                                    amis()->StaticExactControl()
                                        ->value('${user_name}'),
                                    amis()->StaticExactControl()
                                        ->value('${id_card}')
                                        ->copyable(['content' => '${id_card_enc | base64Decode}']),
                                ])
                                ->description('✆ <span class=text-follow>${mobile_enc | base64Decode}</span>')
                                ->visible($isEdit),
                            amis()->StaticExactControl(false, module_enterprise_alias())
                                ->value('${rel.enterprise.enterprise_name}')
                                ->description('<span class=text-follow>${rel.grade.grade_name || rel.department.department_name}</span>${rel.classes?"/":""}<span class=text-follow-dark>${rel.classes.classes_name}</span>')
                                ->visible($isEdit)->visibleOn('${user_type !== "visitor"}'),

                            // ================以下新增时生效==================
                            amis()->TextControl('user_name', '用户姓名')
                                ->hidden($isEdit)
                                ->required(),
                            amis()->TextControl('id_card', '身份证号')
                                ->validateOnChange()
                                ->validations([
                                    'matchRegexp' => '/^[\\d|*]{17}[\\dXx]$/i',
                                ])
                                ->validationErrors([
                                    'matchRegexp' => '请输入有效的中国大陆身份证号码',
                                ])
                                ->hidden($isEdit)
                                ->required(),
                            amis()->TextControl('mobile', '手机号码')
                                ->validations(['matchRegexp' => '/^1[3-9][\\d|*]{9}$/'])
                                ->validationErrors(['matchRegexp' => '请输入有效的中国大陆手机号码'])
                                ->visible(! $isEdit)
                                ->required(),
                            amis()->SelectControl('enterprise_id', module_enterprise_alias())
                                ->options($this->service->getEnterpriseAll())
                                ->hidden($isEdit)
                                ->required(),
                            amis()->SwitchControl('state', '状态')
                                ->onText('正常')
                                ->offText('禁用')
                                ->value(1),

                        ])->className('border-r border-dashed pr-5'),
                        amis()->GroupControl()->body([
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
                                ])
                                ->crop([
                                    'aspectRatio' => '0.81',
                                ])
                                ->onEvent([
                                    'remove' => [
                                        'actions' => [
                                            [
                                                'actionType' => 'ajax',
                                                'type' => 'delete',
                                                'api' => [
                                                    'url' => $this->remove(),
                                                    'method' => 'post',
                                                    'data' => [
                                                        'file' => '${event.data.value}',
                                                    ],
                                                    'messages' => [
                                                        'success' => '文件已清除',
                                                        'fail' => '清除失败',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ]),
                        ]),
                    ]),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()->CheckboxesControl('open_type', '开锁模式')
                            ->options(Enum::open_type())
                            ->required(),
                    ]),
                    amis()->DateTimeControl(false, '更新时间')
                        ->value('${updated_at}')
                        ->visible($isEdit)
                        ->static($isEdit),
                ]),
                amis()->Tab()->title('权限设置')->icon('menu')->body([
                    amis()->GroupControl()->mode('horizontal')->body([
                        //                        amis()->SelectControl('permission_code', '用户权限')
                        //                            ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/permission/all'))
                        //                            ->value(),
                        //                        amis()->DateRangeControl('expiry_date','进出日期')
                        //                            ->valueFormat('YYYY-MM-DD')
                        //                            ->description('<span class=text-blue-300>空值为长期</span>'),
                    ]),
                ]),

            ]),

        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([

            amis()->Tabs()->tabsMode('line')->tabs([
                amis()->Tab()->title('用户信息')->icon('menu')->body([
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()->GroupControl()->direction('vertical')->body([
                            amis()->StaticExactControl('id', 'ID')->visibleOn('${id}')->copyable(),
                            amis()->TagControl('user_type', '类型')
                                ->options(Enum::user_type())
                                ->disabled(),
                            amis()->InputGroupControl(false, '用户')
                                ->body([
                                    amis()->StaticExactControl()
                                        ->value('${user_name}'),
                                    amis()->StaticExactControl()
                                        ->value('${id_card}')
                                        ->copyable(['content' => '${id_card_enc | base64Decode}']),
                                ])
                                ->description('✆ <span class=text-follow>${mobile_enc | base64Decode}</span>')
                                ->visible('${user_type !== "visitor"}'),
                            amis()->StaticExactControl(false, module_enterprise_alias())
                                ->value('${rel.enterprise.enterprise_name}')
                                ->description('<span class=text-follow>${rel.grade.grade_name || rel.department.department_name}</span>${rel.classes?"/":""}<span class=text-follow-dark>${rel.classes.classes_name}</span>'),
                            amis()->SwitchControl('state', '状态')
                                ->onText('正常')
                                ->offText('禁用')
                                ->disabled()
                                ->static(false),
                        ])->className('border-r border-dashed pr-5'),
                        amis()->GroupControl()->body([
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
                                ])
                                ->crop([
                                    'aspectRatio' => '0.81',
                                ]),
                        ]),
                    ]),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()
                            ->CheckboxesControl('open_type', '开锁模式')
                            ->options(Enum::open_type())
                            ->disabled()
                            ->static(false),
                    ]),
                    amis()->DateTimeControl('updated_at', '更新时间')
                        ->valueFormat('YYYY-MM-DD HH:mm:ss')
                        ->value('+0hours'),
                ]),
            ]),

        ])->static();
    }

    public function options(): array
    {
        return $this->service->options();
    }

    /**
     * 获取用户列表
     *
     * @return array
     */
    public function userAll()
    {
        return $this->service->userAll();
    }

    public function getAccessUser()
    {
        return $this->service->getAccessUser();
    }

    public function importAction($api = null): DialogAction
    {
        return amis()->DialogAction()->label('一键导入')->icon('upload')->dialog(
            amis()->Dialog()->title('一键导入')->body([
                amis()->Alert()->showCloseButton()->body('请根据实际情况选择合适的导入对象信息'),
                amis()->Form()->mode('horizontal')->api($api)->body([
                    amis()->Flex()->items([
                        amis()->RadiosControl('user_type', '用户类型')
                            ->options(Enum::user_type(['visitor']))
                            ->clearable()
                            ->required(),
                        amis()->SelectControl('enterprise_id', module_enterprise_alias())
                            ->options($this->service->getEnterpriseAll())
                            ->visibleOn('${user_type}')
                            ->clearValueOnSourceChange()
                            ->clearable()
                            ->required(),
                        amis()->TreeSelectControl('grade_id', '年级')
                            ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade'))
                            ->visibleOn('${enterprise_id && user_type && user_type !== "worker"}')
                            ->clearValueOnSourceChange()
                            ->clearValueOnHidden()
                            ->clearable()
                            ->searchable()
                            ->onlyLeaf()
                            ->required(),
                        amis()->SelectControl('classes_id', '班级')
                            ->source(admin_url('biz/enterprise/${enterprise_id||0}/grade/${grade_id||0}/classes'))
                            ->visibleOn('${enterprise_id && grade_id && user_type && user_type !== "worker"}')
                            ->clearValueOnSourceChange()
                            ->clearValueOnHidden()
                            ->clearable()
                            ->searchable()
                            ->required(),
                        amis()->SelectControl('user_id', '学生')
                            ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/${grade_id||0}/${classes_id||0}/${department_id||0}/${user_type||0}/user'))
                            ->visibleOn('${enterprise_id && grade_id && classes_id && user_type && user_type == "student"}')
                            ->clearValueOnSourceChange()
                            ->clearValueOnHidden()
                            ->maxTagCount(5)
                            ->multiple()
                            ->checkAll()
                            ->clearable()
                            ->searchable(),
                        amis()->SelectControl('user_id', '家长')
                            ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/${grade_id||0}/${classes_id||0}/${department_id||0}/${user_type||0}/user'))
                            ->visibleOn('${enterprise_id && grade_id && classes_id && user_type && user_type == "patriarch"}')
                            ->clearValueOnSourceChange()
                            ->clearValueOnHidden()
                            ->maxTagCount(5)
                            ->multiple()
                            ->checkAll()
                            ->clearable()
                            ->searchable(),
                        amis()->TreeSelectControl('department_id', '部门')
                            ->source(admin_url('biz/worker/${enterprise_id||0}/department/data'))
                            ->visibleOn('${enterprise_id && user_type && user_type == "worker"}')
                            ->onlyChildren(false)
                            ->onlyLeaf(false)
                            ->hideNodePathLabel()
                            ->searchable()
                            ->required(),
                        amis()->SelectControl('user_id', is_school_module() ? '教师' : '员工')
                            ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/${grade_id||0}/${classes_id||0}/${department_id||0}/${user_type||0}/user'))
                            ->visibleOn('${enterprise_id && department_id && user_type && user_type == "worker"}')
                            ->clearValueOnSourceChange()
                            ->clearValueOnHidden()
                            ->maxTagCount(5)
                            ->multiple()
                            ->checkAll()
                            ->clearable()
                            ->searchable(),
                        amis()->CheckboxesControl('open_type', '开锁模式')
                            ->options(Enum::open_type())
                            ->visibleOn('${enterprise_id && user_type}')
                            ->defaultCheckAll()
                            ->required(),
                    ])->direction('column')->style(['gap' => 0]),
                ]),
            ])
            // ->actions()
        );
    }

    public function userImport()
    {
        $res = $this->service->userImport();
        if ($res) {
            return $this->response()->successMessage('导入成功');
        }

        return $this->response()->fail('导入失败，请检查');
    }

    protected function rowSetAction(bool|string $dialog = false, string $dialogSize = 'md', string $title = '')
    {
        $title = $title ?: '设置';
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->setForm()
                ->api($this->getUpdatePath())
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title('【<b class=text-danger>${user_name}</b>】'.$title)->body($form)->size($dialogSize)
                );
            } else {
                $action = amis()->DialogAction()->dialog(
                    amis()->Dialog()->title($title)->body($form)->size($dialogSize)
                );
            }
        }

        $action->label($title)->level('link');

        return AdminPipeline::handle(AdminPipeline::PIPE_EDIT_ACTION, $action);
    }

    private function setForm(): Form
    {
        return $this->baseForm()->body([
            amis()->Alert()
                ->showIcon()
                ->showCloseButton()
                ->style([
                    'padding' => '0.5rem',
                    'borderStyle' => 'dashed',
                ])
                ->body('提示：请确保网络环境可以正常访问'),
            amis()->Tabs()->tabsMode('line')->tabs([
                // 操作权限
                amis()->Tab()->title('基本信息')->icon('menu')->body([
                    amis()->StaticExactControl()
                        ->label('ID')
                        ->value('${id}'),
                    amis()->StaticExactControl()
                        ->label('机构单位')
                        ->value('${rel.school.school_name|raw}')
                        ->static(),
                    amis()->StaticExactControl()->label('设施主体')->value('${rel.facility.level_name|raw}'),
                    amis()->StaticExactControl()->label('设备名称')->value('${device_name}'),
                    amis()->StaticExactControl()->label('设备编码')->value('${device_sn}'),
                    amis()->StaticExactControl()->label('设备描述')->value('${device_desc}'),
                    amis()->StaticExactControl()->label('排序')->value('${sort}'),
                    amis()->SwitchControl()
                        ->name('state')
                        ->label('状态')
                        ->onText('正常')
                        ->offText('禁用')
                        ->disabled(),
                ]),
                // 数据权限
                amis()->Tab()->title('数据权限')->icon('menu')->body([
                    amis()->CheckboxesControl('auth_data', '可授权数据')
                        ->source('system/admin_permissions/1000/data/option?route=')
                        ->mode('normal')
                        ->defaultCheckAll(true)
                        ->checkAll()
                        ->inline(false)
                        ->joinValues()
                        ->columnsCount(array_merge([1], array_fill(0, 300, 2)))
                        ->labelClassName(['w-28' => true])
                        ->options(),

                ]),
            ]),

        ]);
    }

    protected function rowSendAction(bool|string $dialog = false, string $dialogSize = 'md', string $title = '')
    {
        $title = $title ?: '下发到设备';
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->sendForm()
                ->api($this->send())
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->resizable()->closeOnEsc()->closeOnOutside()->title('【<b class=text-danger>${user_name}</b>】'.$title)->body($form)->size($dialogSize)
                );
            } else {
                $action = amis()->DialogAction()->dialog(
                    amis()->Dialog()->title($title)->body($form)->size($dialogSize)
                );
            }
        }

        $action->label($title)->level('link');

        return AdminPipeline::handle(AdminPipeline::PIPE_EDIT_ACTION, $action);
    }

    private function sendForm(): Form
    {
        return $this->baseForm()->body([
            amis()->Alert()
                ->showIcon()
                ->showCloseButton()
                ->style([
                    'padding' => '0.5rem',
                    'borderStyle' => 'dashed',
                ])
                ->body('提示：请确保网络环境可以正常访问'),
            amis()->Tabs()->tabsMode('line')->tabs([
                // 设备列表
                amis()->Tab()->title('设备列表')->icon('menu')->body([
                    amis()->TableControl('device_data', false)
                        ->addable()
                        ->copyable()
                        ->editable()
                        ->removable()
                        ->showIndex()
                        ->perPage(3)
                        ->autoFillHeight()
                        ->columns([
                            amis()->TableColumn('enterprise_id', '机构单位')
                                ->searchable()
                                ->set('type', 'select')
                                ->set('options', $this->service->getEnterpriseAll())
                                ->set('required', true),
                            amis()->TableColumn('facility_id', '设施主体')
                                ->set('type', 'select')
                                // ->set('options', $this->service->options())
                                ->set('static', false)
                                ->set('required', true),
                            amis()->TableColumn('device_id', '设备')
                                ->sortable(),

                        ]),

                ]),
            ]),

        ]);
    }

    public function send()
    {
        $data = [
            'client_id' => 'f3631cb0-a66a5c60',
            'version' => '0.2',
            'cmd' => 'create_face',
            'per_id' => '275191',
            'face_id' => '275191',
            'per_name' => '简子岚',
            'idcardNum' => '520327201101030145',
            'img_data' => '',
            'img_url' => 'http://bjylt.oss-cn-chengdu.aliyuncs.com/image/2026-01/15/520327201101030145.jpg',
            'idcardper' => '520327201101030145',
            's_time' => 0,
            'e_time' => 86400,
            'per_type' => 1,
            'usr_type' => 1,
            'auth_type' => 1,
            'auth_type_name' => 'c2NobWlkdA==',
            'dscode_img' => 'fffffff',
        ];
        $topic = 'face/f3631cb0-a66a5c60/request';

        // MQTT::publish($topic, json_encode($data, JSON_UNESCAPED_UNICODE));
        return true;
    }
}
