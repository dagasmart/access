<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Services\AccessDeviceService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use DagaSmart\Organization\Enums\Enum;


/**
 * 基础-设备类
 *
 * @property AccessDeviceService $service
 */
class AccessDeviceController extends AdminController
{
	protected string $serviceName = AccessDeviceService::class;

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
                amis()->TableColumn('id', 'ID')
                    ->sortable()
                    ->set('fixed','left'),
                amis()->TableColumn('rel.enterprise.enterprise_name', '机构单位')
                    ->searchable([
                        'name' => 'enterprise_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getEnterpriseAll(),
                    ])
                    ->width(200),
                amis()->TableColumn('rel.facility.level_name', '设施主体')
                    ->searchable([
                        'name' => 'facility_id',
                        'type' => 'tree-select',
                        'multiple' => true,
                        'options' => $this->service->options(),
                    ])
                    ->width(200),
                amis()->TableColumn('device_pos','安装位置')
                    ->searchable([
                        'name' => 'device_pos',
                        'type' => 'select',
                        'options' => Enum::DevicePos
                    ])
                    ->set('type', 'select')
                    ->set('options', Enum::DevicePos)
                    ->set('static', true),
                amis()->TableColumn('device_name', '设备名称')->width(200),
                amis()->TableColumn('device_sn','设备编号')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                    ])
                    ->width(150),
                amis()->TableColumn('state', '状态')
                    ->set('type','status'),
                amis()->TableColumn('sort','排序'),
                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->sortable()
                    ->width(150),
                $this->rowActions([
                    amis()->Operation()->label(admin_trans('admin.actions'))->buttons([
                        $this->rowShowButton(true),
                        $this->rowSetAction('drawer', 'auto'),
                        $this->rowEditButton(true,250),
                        $this->rowDeleteButton(),
                    ])
                ])
                    ->set('align','center')
                    ->set('fixed','right')
                    ->set('width',180)
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
                ->options(Enum::brand('access'))
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
                amis()->SelectControl('device_pos','安装位置')
                    ->options(Enum::DevicePos)
                    ->placeholder('安装位置')
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
                ->options(Enum::brand('access'))
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
            amis()->SelectControl('device_pos','安装位置')
                ->options(Enum::DevicePos)
                ->placeholder('安装位置')
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
                    amis()->Drawer()->closeOnEsc()->closeOnOutside()->title('【<font color="orangered">${device_name}</font>】' .$title)->body($form)->size($dialogSize)
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


}
