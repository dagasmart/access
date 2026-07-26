<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Services\AccessPermissionService;
use DagaSmart\BizAdmin\Controllers\AdminController;
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
                ...$this->baseHeaderToolBar(),
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
                    ->set('fixed', 'left')
                    ->width(200),
                amis()->TableColumn('permission_name', '权限名')
                    ->searchable([
                        'name' => 'permission_name',
                        'type' => 'input-text',
                    ])
                    ->set('fixed', 'left'),
                amis()->TableColumn('permission_code', '权限码')
                    ->set('type', 'input-tag')
                    ->set('options', $this->service->permissionCode())
                    ->set('static', true),
                amis()->TableColumn('permission_combo', '权限内容')
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('inline')->body([
                            //                            amis()->TagControl('combo','${combo ? "星期" : null}')
                            //                                ->mode('horizontal')
                            //                                ->options($this->service->arrayWeeks())
                            //                                ->static(),
                            //                            amis()->Button()->label('明细')->level('link')->onEvent(),
                            amis()->Tag()->label('${combo ? "星期" + combo : null}')->className('text-ellipsis'),
                            amis()->Button()->label('明细')->level('link')->size('xs')->onEvent([
                                'click' => [
                                    'actions' => [
                                        [
                                            'actionType' => 'dialog',
                                            'dialog' => [
                                                'type' => 'dialog',
                                                'title' => '权限内容',
                                                'actions' => [],
                                                'size' => 'md',
                                                'closeOnEsc' => true,
                                                'body' => [
                                                    amis()->ComboControl('permission_combo', false)
                                                        ->items([
                                                            // amis()->Tag()->label('星期${index+1}'),
                                                            amis()->SubFormControl('week${index+1}', '星期${["日","一","二","三","四","五","六","日"][index+1]}')
                                                                ->multiple()
                                                                ->btnLabel('${begin}-${end}')
                                                                ->draggable(false)
                                                                ->addable(false)
                                                                ->removable(false)
                                                                ->form([
                                                                    'title' => '时间范围',
                                                                    'body' => [
                                                                        // amis()->InputTimeRange()->name('begin')->label('选择时间')->extraName('end')->required(),
                                                                        amis()->TimeControl('begin', '开始时间')
                                                                            ->valueFormat('HH:mm')
                                                                            ->clearable()
                                                                            ->static(),
                                                                        amis()->TimeControl('end', '结束时间')
                                                                            ->valueFormat('HH:mm')
                                                                            ->timeConstraints([
                                                                                'hours' => ['min' => 0, 'max' => 23],
                                                                                'minutes' => ['min' => 0, 'max' => 59],
                                                                            ])
                                                                            ->clearable()
                                                                            ->static(),
                                                                    ],
                                                                    'closeOnEsc' => true,
                                                                    'actions' => [],
                                                                    'size' => 'sm',
                                                                    'static' => true,
                                                                ])
                                                                ->static(),
                                                        ])
                                                        ->formClassName('border-b border-dashed')
                                                        ->maxLength(7)
                                                        ->multiple()
                                                        ->draggable()
                                                        ->static(),
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ]),
                    ])
                    ->width(220),
                amis()->TableColumn('exclude_date', '指定日期禁止通行')
                    ->searchable([
                        'name' => 'is_exclude',
                        'type' => 'checkboxes',
                        'multiple' => true,
                        'options' => $this->service->switchOption(),
                    ])
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('horizontal')->body([
                            amis()->SwitchControl('is_exclude')->onText('是')->offText('否')->disabled(),
                            amis()->Button()->label('明细')->level('link')->size('xs')->onEvent([
                                'click' => [
                                    'actions' => [
                                        [
                                            'actionType' => 'dialog',
                                            'dialog' => [
                                                'type' => 'dialog',
                                                'title' => '禁止通行',
                                                'actions' => [],
                                                'closeOnEsc' => true,
                                                'body' => [
                                                    amis()->SwitchControl('is_exclude', '指定日期禁止通行')
                                                        ->labelWidth('auto')
                                                        ->onText('是')
                                                        ->offText('否')
                                                        ->disabled()
                                                        ->static(false),
                                                    amis()->ComboControl('exclude_date', false)
                                                        ->items([
                                                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                                                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                                                            amis()->Button()->label('日期 ${index+1}')->level('link'),
                                                            amis()->DateControl('begin', '时间段')
                                                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                                                ->placeholder('请选择开始日期')
                                                                ->minDate('${today()}')
                                                                ->static(),
                                                            amis()->DateControl('end', false)
                                                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                                                ->placeholder('请选择结束日期')
                                                                ->minDate('${begin||today()}')
                                                                ->static(),
                                                        ])
                                                        ->formClassName('border-b border-dashed')
                                                        ->multiple()
                                                        ->draggable()
                                                        ->visibleOn('${!!is_exclude}')
                                                        ->static(),
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ]),
                    ])
                    ->width(150),
                amis()->TableColumn('allow_date', '指定日期允许通行')
                    ->searchable([
                        'name' => 'is_allow',
                        'type' => 'checkboxes',
                        'multiple' => true,
                        'options' => $this->service->switchOption(),
                    ])
                    ->set('type', 'page')
                    ->set('body', [
                        amis()->GroupControl()->mode('horizontal')->body([
                            amis()->SwitchControl('is_allow')->onText('是')->offText('否')->disabled(),
                            amis()->Button()->label('明细')->level('link')->size('xs')->onEvent([
                                'click' => [
                                    'actions' => [
                                        [
                                            'actionType' => 'dialog',
                                            'dialog' => [
                                                'type' => 'dialog',
                                                'title' => '允许通行',
                                                'actions' => [],
                                                'closeOnEsc' => true,
                                                'body' => [
                                                    amis()->SwitchControl('is_allow', '指定日期允许通行')
                                                        ->labelWidth('auto')
                                                        ->onText('是')
                                                        ->offText('否')
                                                        ->disabled()
                                                        ->static(false),
                                                    amis()->ComboControl('allow_date', false)
                                                        ->items([
                                                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                                                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                                                            amis()->Button()->label('日期 ${index+1}')->level('link'),
                                                            amis()->DateControl('begin', '时间段')
                                                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                                                ->placeholder('请选择开始日期')
                                                                ->minDate('${today()}')
                                                                ->static(),
                                                            amis()->DateControl('end', false)
                                                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                                                ->placeholder('请选择结束日期')
                                                                ->minDate('${begin||today()}')
                                                                ->static(),
                                                        ])
                                                        ->formClassName('border-b border-dashed')
                                                        ->multiple()
                                                        ->draggable()
                                                        ->visibleOn('${!!is_allow}')
                                                        ->static(),
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ]),
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
                            ->source(admin_url('extension/access/enterprise/${enterprise_id||0}/permission/${id||0}/code'))
                            ->size('sm')
                            ->value('${rel.permission_name}')
                            ->disabledOn('${!enterprise_id}')
                            ->required(),
                    ]),
                ]),
                amis()->Tab()->title('时间设定')->icon('menu')->body([
                    amis()->ComboControl('permission_combo', false)
                        ->items([
                            // amis()->Tag()->label('星期${index+1}'),
                            amis()->SubFormControl('week${index+1}', '星期${["日","一","二","三","四","五","六","日"][index+1]}')
                                ->multiple()
                                ->btnLabel('${begin}-${end}')
                                ->draggable()
                                ->addable()
                                ->removable()
                                ->form([
                                    'title' => '时间范围',
                                    'body' => [
                                        // amis()->InputTimeRange()->name('begin')->label('选择时间')->extraName('end')->required(),
                                        amis()->TimeControl('begin', '开始时间')
                                            ->valueFormat('HH:mm')
                                            ->clearable()
                                            ->required(),
                                        amis()->TimeControl('end', '结束时间')
                                            ->valueFormat('HH:mm')
                                            ->timeConstraints([
                                                'hours' => ['min' => 0, 'max' => 23],
                                                'minutes' => ['min' => 0, 'max' => 59],
                                            ])
                                            ->clearable()
                                            ->required(),
                                    ],
                                    'size' => 'sm',
                                ])
                                ->required(),
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
                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                            amis()->Button()->label('禁止日期 ${index+1}')->level('link'),
                            amis()->DateControl('begin')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择开始日期')
                                ->minDate('${today()}')
                                ->required(),
                            amis()->DateControl('end')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择结束日期')
                                ->minDate('${begin||today()}')
                                ->required(),
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
                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                            amis()->Button()->label('允许日期 ${index+1}')->level('link'),
                            amis()->DateControl('begin')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择开始日期')
                                ->minDate('${today()}')
                                ->required(),
                            amis()->DateControl('end')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择结束日期')
                                ->minDate('${begin||today()}')
                                ->required(),
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
            amis()->Tabs()->tabsMode('line')->tabs([
                amis()->Tab()->title('基本信息')->icon('menu')->body([
                    amis()->GroupControl()->mode('normal')->body([
                        amis()->SelectControl('enterprise_id', module_enterprise_alias())
                            ->options($this->service->getEnterpriseAll())
                            ->value('${rel.enterprise_name}')
                            ->size('lg')
                            ->searchable()
                            ->clearable()
                            ->disabled()
                            ->required(),
                        amis()->TextControl('permission_name', '权限名')
                            ->clearable()
                            ->required(),
                        amis()->SelectControl('permission_code', '权限码')
                            ->options($this->service->permissionCode())
                            ->source(admin_url('extension/access/enterprise/${enterprise_id||0}/permission/${id||0}/code'))
                            ->size('sm')
                            ->value('${rel.permission_name}')
                            ->disabledOn('${!enterprise_id}')
                            ->required(),
                    ]),
                ]),
                amis()->Tab()->title('时间设定')->icon('menu')->body([
                    amis()->ComboControl('permission_combo', false)
                        ->items([
                            // amis()->Tag()->label('星期${index+1}'),
                            amis()->SubFormControl('week${index+1}', '星期${["日","一","二","三","四","五","六","日"][index+1]}')
                                ->multiple()
                                ->btnLabel('${begin}-${end}')
                                ->draggable(false)
                                ->addable(false)
                                ->removable(false)
                                ->form([
                                    'title' => '时间范围',
                                    'body' => [
                                        // amis()->InputTimeRange()->name('begin')->label('选择时间')->extraName('end')->required(),
                                        amis()->TimeControl('begin', '开始时间')
                                            ->valueFormat('HH:mm')
                                            ->clearable()
                                            ->required(),
                                        amis()->TimeControl('end', '结束时间')
                                            ->valueFormat('HH:mm')
                                            ->timeConstraints([
                                                'hours' => ['min' => 0, 'max' => 23],
                                                'minutes' => ['min' => 0, 'max' => 59],
                                            ])
                                            ->clearable()
                                            ->required(),
                                    ],
                                    'closeOnEsc' => true,
                                    'actions' => [],
                                    'size' => 'sm',
                                    'static' => true,
                                ])
                                ->required(),
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
                        ->offText('否')
                        ->disabled()
                        ->static(false),
                    amis()->ComboControl('exclude_date', false)
                        ->items([
                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                            amis()->Button()->label('禁止日期 ${index+1}')->level('link'),
                            amis()->DateControl('begin', '开始日期')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择开始日期')
                                ->minDate('${today()}')
                                ->required(),
                            amis()->DateControl('end', '结束日期')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择结束日期')
                                ->minDate('${begin||today()}')
                                ->required(),
                        ])
                        ->formClassName('border-b border-dashed')
                        ->multiple()
                        ->draggable()
                        ->visibleOn('${!!is_exclude}')
                        ->required(),
                    amis()->Divider()->lineStyle('dashed'),
                    amis()->SwitchControl('is_allow', '指定日期允许通行')
                        ->labelWidth('auto')
                        ->onText('是')
                        ->offText('否')
                        ->disabled()
                        ->static(false),
                    amis()->ComboControl('allow_date', false)
                        ->items([
                            // amis()->DateControl('date','日期${index+1}')->size('lg')->required(),
                            // amis()->InputTimeRange()->name('begin')->extraName('end')->required(),
                            amis()->Button()->label('允许日期 ${index+1}')->level('link'),
                            amis()->DateControl('begin', '开始日期')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择开始日期')
                                ->minDate('${today()}')
                                ->required(),
                            amis()->DateControl('end', '结束日期')
                                ->shortcuts(['today', 'tomorrow', '7dayslater'])
                                ->placeholder('请选择结束日期')
                                ->minDate('${begin||today()}')
                                ->required(),
                        ])
                        ->formClassName('border-b border-dashed')
                        ->multiple()
                        ->draggable()
                        ->visibleOn('${!!is_allow}')
                        ->required(),
                ]),
            ]),
        ])->static();
    }

    /**
     * 权限码表
     */
    public function permissionCode()
    {
        return $this->service->permissionCode();
    }

    /**
     * 机构权限列表
     */
    public function permissionAll()
    {
        return $this->service->permissionAll();
    }
}
