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
                amis()->TableColumn('permission_name','权限名')
                    ->searchable([
                        'name' => 'permission_name',
                        'type' => 'input-text',
                    ])
                    ->set('fixed','left'),
                amis()->TableColumn('permission_code','权限码'),
                amis()->TableColumn('enterprise_id', module_enterprise_alias())
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
                    ->width(200),
                amis()->TableColumn('permission_combo', '权限内容')->width(200),
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
                    ->set('href', '342343')
                    ->width(150),
                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->sortable()
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
                ->clearable()
                ->required(),
            amis()->InputGroupControl('device_sn','设备编号')->body([
                amis()->TextControl('device_sn', '设备编号')
                    ->placeholder('请填写设备编号，如sn')
                    ->clearable()
                    ->required(),
                amis()->SelectControl('device_pos','安装位置')
                    ->options([['label' => '进口入场', 'value' => 'in'],['label' => '出口离场', 'value' => 'out']])
                    ->placeholder('安装位置')
                    ->required(),
            ]),
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


}
