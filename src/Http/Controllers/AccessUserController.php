<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Access\Services\AccessUserService;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Access\Enums\Enum;
//use PhpMqtt\Client\Facades\MQTT;


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
				...$this->baseHeaderToolBar()
			])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('user_id', 'ID')
                    ->sortable()
                    ->set('fixed','left'),
                amis()->TableColumn('user_card', '用户')
                    ->searchable(amis()->FormControl()->body([
                        amis()->TextControl('user_name', '用户名')->placeholder('请输入查找的用户名')->clearable(),
                        amis()->TextControl('id_card', '身份证号')->placeholder('请输入查找的身份证号')->clearable(),
                    ]))
                    ->set('type', 'tpl')
                    ->set('tpl', '${user_name}<h5 class="m-0 mt-1.5 text-secondary">${id_card}</h5>')
                    ->align('center')
                    ->width(100),
                amis()->TableColumn('rel.enterprise.enterprise_name', '单位信息')
                    ->searchable([
                        'name' => 'enterprise_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getEnterpriseAll(),
                    ])
                    ->set('type', 'tpl')
                    ->set('tpl', '${rel.enterprise.enterprise_name}<h5 class="m-0 mt-1 text-secondary">${rel.grade.grade_name}</h5><h5 class="m-0 mt-1.5 text-secondary">${rel.classes.classes_name}</h5>')
                    ->width(200),
                amis()->TableColumn('user_avatar','照片')
                    ->set('type', 'avatar')
                    ->set('src', '${user_avatar}')
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
                                                ->src('${user_avatar}')
                                                ->defaultImage(url(admin_config('admin.default_image')))
                                                ->width('100%')
                                                ->height('100%'),
                                        ]
                                    ]
                                ]
                            ]
                        ]
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
                    ->set('type','switch')
                    ->set('onText','正常')
                    ->set('offText','停用'),
                amis()->TableColumn('sort','排序')->sortable(),
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
                        $this->rowSetAction('drawer', 'auto'),
                        $this->rowSendAction('drawer', 'lg'),
                    ])
                ])
                    ->set('align','center')
                    ->set('fixed','right')
                    ->set('width',150)
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
                            ->RadiosControl('user_type','用户类型')
                            ->options(Enum::user_type())
                            ->value('visitor')
                            ->disabled($isEdit)
                            ->visible(!$isEdit),
                    ]),
                    amis()->Divider()->lineStyle('dashed')->visible(!$isEdit),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()->GroupControl()->direction('vertical')->body([
                            amis()->StaticExactControl('user_id','ID')->visibleOn('${id}')->copyable(),
                            amis()->TagControl('user_type','用户类型')
                                ->options(Enum::user_type())
                                ->static('${user_type !== "visitor"}')
                                ->disabledOn('${user_type !== "visitor"}')
                                ->visible($isEdit),
                            amis()->StaticExactControl(false,'用户姓名')
                                ->value('${user_name}')
                                ->description('<span class=text-red-300>${id_card}</span>')
                                ->copyable()
                                ->static('${user_type !== "visitor"}')
                                ->visible($isEdit),
                            amis()->StaticExactControl(false, module_enterprise_alias())
                                ->value('${rel.enterprise.enterprise_name}')
                                ->description('<span class=text-blue-300>${rel.grade.grade_name}</span>/<span class=text-blue-300>${rel.classes.classes_name}</span>')
                                ->visible($isEdit),
                            amis()->TextControl('user_name','用户姓名')
                                ->hidden($isEdit)
                                ->required(),
                            amis()->TextControl('id_card','身份证号')
                                ->hidden($isEdit)
                                ->required(),
                            amis()->SelectControl('enterprise_id', module_enterprise_alias())
                                ->options($this->service->getEnterpriseAll())
                                ->hidden($isEdit)
                                ->required(),

                            amis()->DateTimeControl('updated_at', '创建时间')->valueFormat('YYYY-MM-DD HH:mm:ss')->value('+0hours'),
                        ])->className('border-r border-dashed pr-5'),
                        amis()->GroupControl()->body([
                            amis()->ImageControl('user_avatar')
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
                                ])
                                ->crop([
                                    'aspectRatio' => '0.81',
                                ]),
                        ]),
                    ]),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()
                            ->CheckboxesControl('open_type','开锁模式')
                            ->options(Enum::open_type()),
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
                            amis()->StaticExactControl('user_id','ID')->visibleOn('${id}')->copyable(),
                            amis()->TagControl('user_type','用户类型')
                                ->options(Enum::user_type())
                                ->static('${user_type !== "visitor"}')
                                ->disabledOn('${user_type !== "visitor"}'),
                            amis()->StaticExactControl(false,'用户姓名')
                                ->value('${user_name}')
                                ->description('<span class=text-red-300>${id_card}</span>')
                                ->copyable()
                                ->static('${user_type !== "visitor"}'),
                            amis()->StaticExactControl(false, module_enterprise_alias())
                                ->value('${rel.enterprise.enterprise_name}')
                                ->description('<span class=text-blue-300>${rel.grade.grade_name}</span>/<span class=text-blue-300>${rel.classes.classes_name}</span>'),

                            amis()->DateTimeControl('updated_at', '创建时间')->valueFormat('YYYY-MM-DD HH:mm:ss')->value('+0hours'),
                        ])->className('border-r border-dashed pr-5'),
                        amis()->GroupControl()->body([
                            amis()->ImageControl('user_avatar')
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
                                ])
                                ->crop([
                                    'aspectRatio' => '0.81',
                                ]),
                        ]),
                    ]),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()
                            ->CheckboxesControl('open_type','开锁模式')
                            ->options(Enum::open_type())
                            ->disabled()
                            ->static(false),
                    ]),
                ]),
            ]),

		])->static();
	}

    public function options(): array
    {
        return $this->service->options();
    }


    protected function rowSetAction(bool|string $dialog = false, string $dialogSize = 'md', string $title = '')
    {
        $title  = $title ?: '设置';
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->setForm()
                ->api($this->getUpdatePath())
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title('【<font color="orangered">${user_name}</font>】' .$title)->body($form)->size($dialogSize)
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
                //操作权限
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
                        ->onText('开启')
                        ->offText('禁用')
                        ->disabled()
                ]),
                //数据权限
                amis()->Tab()->title('数据权限')->icon('menu')->body([
                    amis()->CheckboxesControl('auth_data', '可授权数据')
                        ->source('system/admin_permissions/1000/data/option?route=')
                        ->mode('normal')
                        ->defaultCheckAll(true)
                        ->checkAll()
                        ->inline(false)
                        ->joinValues()
                        ->columnsCount(array_merge([1],array_fill(0, 300, 2)))
                        ->labelClassName(['w-28' => true])
                        ->options()

                ])
            ]),

        ]);
    }


    protected function rowSendAction(bool|string $dialog = false, string $dialogSize = 'md', string $title = '')
    {
        $title  = $title ?: '下发到设备';
        $action = amis()->LinkAction()->link($this->getEditPath());

        if ($dialog) {
            $form = $this
                ->sendForm()
                ->api($this->send())
                ->redirect('');

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->resizable()->closeOnEsc()->closeOnOutside()->title('【<font color="orangered">${user_name}</font>】' .$title)->body($form)->size($dialogSize)
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
                //设备列表
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
                                ->set('options', $this->service->options())
                                ->set('static', false)
                                ->set('required', true),
                            amis()->TableColumn('device_id', '设备')
                                ->sortable(),

                    ])


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
            'dscode_img' => 'fffffff'
        ];
        $topic = 'face/f3631cb0-a66a5c60/request';
        //MQTT::publish($topic, json_encode($data, JSON_UNESCAPED_UNICODE));
        return true;
    }


}
