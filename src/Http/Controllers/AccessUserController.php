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
				$this->createButton('dialog',250),
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
                    ->set('static', true),
                amis()->TableColumn('user_type', '用户类型')
                    ->searchable(
                        amis()->SelectControl('user_type')->options(Enum::user_type())->checkAll()->multiple()->clearable(),
                    )
                    ->set('type', 'select')
                    ->set('options', Enum::user_type())
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
                        $this->rowEditButton(true,250),
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
            amis()->SelectControl('enterprise_id', '机构单位')
                ->options($this->service->getEnterpriseAll())
                ->value('${rel.enterprise_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('facility_id', '设施主体')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/options'))
                ->options($this->service->options())
                ->value('${rel.facility.id}')
                ->disabledOn('${!enterprise_id}')
                ->onlyLeaf()
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TextControl('device_name', '设备名称')
                ->placeholder('例:智能门禁机-进-1')
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('device_brand', '设备品牌')
                ->options(Enum::user_type('access'))
                ->placeholder('请选择品牌')
                ->clearable()
                ->required(),
            amis()->TextControl('device_model', '设备型号')
                ->placeholder('设备型号，如ET293')
                ->clearable()
                ->required(),
            amis()->InputGroupControl('device_sn','设备编号')->body([
                amis()->TextControl('device_sn', '设备编号')
                    ->placeholder('请填写设备编号，如sn')
                    ->clearable()
                    ->required(),
            ])->required(),
            amis()->TextareaControl('device_desc', '设备描述')
                ->clearable(),
            amis()->NumberControl('sort', '排序')
                ->min(0)
                ->max(100)
                ->size('xs')
                ->value(10),
            amis()->SwitchControl('state','状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true),
		]);
	}

	public function detail(): Form
    {
		return $this->baseDetail()->body([
            amis()->StaticExactControl('id','ID')->visibleOn('${id}'),
            amis()->SelectControl('enterprise_id', '机构单位')
                ->options($this->service->getEnterpriseAll())
                ->value('${rel.school.id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('facility_id', '选择主体')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/options'))
                ->options($this->service->options())
                ->disabledOn('${!enterprise_id}')
                ->value('${rel.facility.id}')
                ->searchable()
                ->clearable(),
            amis()->TextControl('device_name', '设备名称')
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('device_brand', '设备品牌')
                ->options(Enum::user_type('access'))
                ->placeholder('请选择品牌')
                ->clearable()
                ->required(),
            amis()->TextControl('device_model', '设备型号')
                ->placeholder('设备型号，如ET293')
                ->clearable(),
//            amis()->TextControl('device_model', '设备型号')
//                ->clearable(),
            amis()->TextControl('device_sn', '设备编号')
                ->placeholder('请填写设备编号，如sn')
                ->clearable()
                ->required(),
            amis()->TextareaControl('device_desc', '设备描述')
                ->clearable(),
            amis()->NumberControl('sort', '排序')
                ->min(0)
                ->max(100)
                ->size('xs')
                ->value(10),
            amis()->SwitchControl('state','状态')
                ->onText('开启')
                ->offText('禁用')
                ->value(true)
                ->disabled()
                ->static(false),
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
