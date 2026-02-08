<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\Access\Services\AccessPermissionService;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;

class AccessPermissionController extends AdminController
{
    protected string $serviceName = AccessPermissionService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton(true),
                ...$this->baseHeaderToolBar()
            ])
            ->autoGenerateFilter()
            ->affixHeader()
            ->columnsTogglable()
            ->footable(['expand' => 'first'])
            ->autoFillHeight(true)
            ->combineNum(1)
            ->columns([
                amis()->TableColumn('enterprise_id', module_enterprise_alias())
                    ->sortable()
                    ->searchable([
                        'name' => 'enterprise_id',
                        'type' => 'select',
                        'multiple' => false,
                        'searchable' => true,
                        'options' => $this->service->getEnterpriseAll(),
                    ])
                    ->set('type', 'select')
                    ->set('options', $this->service->getEnterpriseAll())
                    ->set('static', true)
                    ->set('fixed','left')
                    ->width(200),
                amis()->TableColumn('permission_name','权限名')
                    ->searchable([
                        'name' => 'permission_name',
                        'type' => 'input-text',
                    ])
                    ->set('fixed','left'),
                amis()->TableColumn('permission_code','权限码')
                    ->set('type', 'input-tag')
                    ->set('options', $this->service->permissionCode())
                    ->set('static', true),
                amis()->TableColumn('permission_combo', '权限内容')
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('horizontal')->body([
                            //amis()->ComboControl('permission_combo'),

                            amis()->Link()->body('明细')->onEvent(),
                        ])
                    ])
                    ->width(200),
                amis()->TableColumn('exclude_date','指定日期禁止通行')
                    ->searchable([
                        'name' => 'is_exclude',
                        'type' => 'checkboxes',
                        'multiple' => true,
                        'options' => $this->service->switchOption()
                    ])
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('horizontal')->body([
                            amis()->SwitchControl('is_exclude')->onText('是')->offText('否')->disabled(),
                            amis()->Link()->body('明细')->onEvent(),
                        ])
                    ])
                    ->width(150),
                amis()->TableColumn('allow_date','指定日期允许通行')
                    ->searchable([
                        'name' => 'is_allow',
                        'type' => 'checkboxes',
                        'multiple' => true,
                        'options' => $this->service->switchOption()
                    ])
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('horizontal')->body([
                            amis()->SwitchControl('is_allow')->onText('是')->offText('否')->disabled(),
                            amis()->Link()->body('明细')->onEvent(),
                        ])
                    ])
                    ->width(150),
                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->width(150),
                $this->rowActions([
                    amis()->Operation()->label(admin_trans('admin.actions'))->buttons([
                        $this->rowShowButton(true),
                        $this->rowEditButton(true),
                        $this->rowDeleteButton(),
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
                amis()->Tab()->title('基本信息')->icon('menu')->body([
                    amis()->GroupControl()->mode('normal')->body([
                        amis()->SelectControl('enterprise_id', module_enterprise_alias())
                            ->options($this->service->getEnterpriseAll())
                            ->value('${rel.enterprise_name}')
                            ->size('lg')
                            ->searchable()
                            ->clearable()
                            ->disabled($isEdit)
                            ->required(),
                        amis()->TextControl('permission_name', '权限名')
                            ->clearable()
                            ->required(),
                        amis()->SelectControl('permission_code', '权限码')
                            ->options($this->service->permissionCode())
                            ->source(admin_url('biz/access/enterprise/${enterprise_id||0}/permission/${id||0}/code'))
                            ->size('sm')
                            ->value('${rel.permission_name}')
                            ->disabledOn('${!enterprise_id}')
                            ->required(),
                    ]),
                ]),
                amis()->Tab()->title('时间设定')->icon('menu')->body([
                    amis()->ComboControl('permission_combo', false)
                        ->items([
                            //amis()->Tag()->label('星期${index+1}'),
                            amis()->SubFormControl('week${index+1}', '星期${["日","一","二","三","四","五","六","日"][index+1]}')
                                ->multiple()
                                ->btnLabel('${begin}-${end}')
                                ->draggable()
                                ->addable()
                                ->removable()
                                ->form([
                                    'title' => '时间范围',
                                    'body' => [
                                        //amis()->InputTimeRange()->name('begin')->label('选择时间')->extraName('end')->required(),
                                        amis()->TimeControl('begin','开始时间')
                                            ->valueFormat('HH:mm')
                                            ->clearable()
                                            ->required(),
                                        amis()->TimeControl('end','结束时间')
                                            ->valueFormat('HH:mm')
                                            ->timeConstraints([
                                                'hours' => ['min' => 0, 'max' => 23],
                                                'minutes' => ['min' => 0, 'max' => 59],
                                            ])
                                            ->clearable()
                                            ->required(),
                                    ],
                                ])->required(),
                        ])
                        ->formClassName('border-b border-dashed')
                        ->multiple()
                        ->maxLength(7)
                        ->draggable(),
                ]),
                amis()->Tab()->title('扩展条件')->icon('menu')->body([
                    amis()->SwitchControl('is_exclude', '指定日期禁止通行')
                        ->labelWidth('auto')
                        ->onText('是')
                        ->offText('否'),
                    amis()->ComboControl('exclude_date', false)
                        ->items([
                            amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                        ])
                        ->multiple()
                        ->draggable()
                        ->visibleOn('${!!is_exclude}')
                        ->required(),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->SwitchControl('is_allow', '指定日期允许通行')
                        ->labelWidth('auto')
                        ->onText('是')
                        ->offText('否'),
                    amis()->ComboControl('allow_date', false)
                        ->items([
                            amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                        ])
                        ->multiple()
                        ->draggable()
                        ->visibleOn('${!!is_allow}')
                        ->required(),
                ]),
            ]),

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
            amis()->TreeSelectControl('parent_id', '选择主体')
                ->source(admin_url('biz/enterprise/${enterprise_id||0}/facility/options'))
                ->options($this->service->options())
                ->disabledOn('${!enterprise_id}')
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

    /**
     *
     */
    public function permissionCode()
    {
        return $this->service->permissionCode();
    }


}
