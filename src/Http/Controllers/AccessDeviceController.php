<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Services\AccessDeviceService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;


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
                amis()->TableColumn('school_id', '学校')
                    ->searchable([
                        'name' => 'school_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getSchoolAll(),
                    ])
                    ->set('type', 'select')
                    ->set('options', $this->service->getSchoolAll())
                    ->set('value', '${rel.school.id}')
                    ->set('static', true)
                    ->width(200),
                amis()->TableColumn('device_name', '设备名称')->width(200),
                amis()->TableColumn('rel.facility.level_name', '设施主体')
                    ->searchable([
                        'name' => 'facility_id',
                        'type' => 'tree-select',
                        'multiple' => true,
                        'options' => $this->service->options(),
                    ])
                    ->width(200),
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
            amis()->SelectControl('school_id', '学校')
                ->options($this->service->getSchoolAll())
                ->value('${rel.school_id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('facility_id', '设施主体')
                ->source(admin_url('biz/school/${school_id||0}/facility/options'))
                ->options($this->service->options())
                ->value('${rel.facility.id}')
                ->disabledOn('${!school_id}')
                ->onlyLeaf()
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TextControl('device_name', '设备名称')
                ->clearable()
                ->required(),
            amis()->TextControl('device_sn', '设备编号')
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
                ->value(true),
		]);
	}

	public function detail(): Form
    {
		return $this->baseDetail()->body([
            amis()->StaticExactControl('id','ID')->visibleOn('${id}'),
            amis()->SelectControl('school_id', '学校')
                ->options($this->service->getSchoolAll())
                ->value('${rel.school.id}')
                ->searchable()
                ->clearable()
                ->required(),
            amis()->TreeSelectControl('parent_id', '选择主体')
                ->source(admin_url('biz/school/${school_id||0}/facility/options'))
                ->options($this->service->options())
                ->disabledOn('${!school_id}')
                ->searchable()
                ->clearable(),
            amis()->TextControl('device_name', '设备名称')
                ->clearable()
                ->required(),
            amis()->TextControl('device_code', '设备编码')
                ->clearable(),
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
                ->body('提示：授权可见操作和数据'),
            amis()->HiddenControl('id', 'ID')->static(),
            amis()->HiddenControl('parent_id', '上级id')->static(),
            amis()->HiddenControl('name','名称')->static(),
            amis()->HiddenControl('slug','标识')->static(),
            amis()->HiddenControl('code','权限加密')->static(),
            amis()->HiddenControl('http_path','路由')->static(),
            amis()->HiddenControl('isAuth','授权操作')->value(true),

            amis()->Tabs()->tabsMode('line')->tabs([
                //操作权限
                amis()->Tab()->title('操作权限')->icon('menu')->body([
                    amis()->CheckboxesControl('auth_oper', '可授权以下操作')
                        ->source('system/admin_permissions/1000/oper/option')
                        ->mode('normal')
                        ->defaultCheckAll(true)
                        ->checkAll()
                        ->inline(true)
                        ->creatable(is_administrator() || is_module_administrator())
                        ->editable(is_administrator() || is_module_administrator())
                        ->removable(is_administrator() || is_module_administrator())
                        ->columnsCount()
                        ->createBtnLabel('新增选项')
                        ->addControls([
                            amis()->TextControl('label','名称')->placeholder('操作权限名称，如：开始上传')->required(),
                            amis()->TextControl('value','标识')->placeholder('操作权限标识，如：upload')->required(),
                        ])->addApi(is_administrator() || is_module_administrator() ? '/system/admin_permissions/1000/oper/save' : false)
                        ->editControls([
                            amis()->TextControl('label','名称')->placeholder('操作权限名称，如：开始上传')->required(),
                            amis()->TextControl('value','标识')->placeholder('操作权限标识，如：upload')->disabled(),
                        ])->editApi(is_administrator() || is_module_administrator() ? '/system/admin_permissions/1000/oper/edit' : false)
                        ->deleteConfirmText('是否删除自定义项【${label}】，将不可恢复')
                        ->deleteApi(is_administrator() || is_module_administrator() ? '/system/admin_permissions/1000/oper/${value}/delete' : false)
                        ->labelClassName(['w-28' => true])
                        ->inputClassName(['p-1' => true])
                        ->options(array(current($this->service->options())))
                        ->onEvent([
                            'addConfirm' => [
                                'actions' => [
                                    [
                                        'actionType' => 'reload',
                                        'componentName' => 'auth_oper'
                                    ],[
                                        'actionType' => 'reload',
                                        'componentId' => 'auth_oper_${code}',
                                    ],
                                ]
                            ],
                            'editConfirm' => [
                                'actions' => [
                                    [
                                        'actionType' => 'reload',
                                        'componentName' => 'auth_oper'
                                    ],[
                                        'actionType' => 'reload',
                                        'componentId' => 'auth_oper_${code}',
                                    ],
                                ]
                            ],
                            'deleteConfirm' => [
                                'actions' => [
                                    [
                                        'actionType' => 'custom',
                                        'script' => 'window.$owl.refreshAmisPage()'
                                    ]
                                ]
                            ]
                        ]),
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
