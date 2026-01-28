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
                amis()->TableColumn('user_name','用户姓名')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                    ])
                    ->set('fixed','left'),
                amis()->TableColumn('device_pos','行为事件')
                    ->searchable([
                        'name' => 'device_pos',
                        'type' => 'select',
                        'options' => [['label' => '进口入场', 'value' => 'in'],['label' => '出口离场', 'value' => 'out']]
                    ])
                    ->set('type', 'mapping')
                    ->set('map', [
                        'in' => '<span class="label label-success rounded-full border">进口入场</span>',
                        'out' => '<span class="label label-danger rounded-full border">出口离场</span>'
                    ])
                    ->set('static', true),
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
                amis()->TableColumn('rel.device.device_name', '设备名称')->width(200),
                amis()->TableColumn('rel.device.device_sn','设备编号')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                    ])
                    ->width(150),
                amis()->TableColumn('updated_at', '更新时间')
                    ->type('datetime')
                    ->sortable()
                    ->width(150),
                $this->rowActions([
                    amis()->Operation()->label(admin_trans('admin.actions'))->buttons([
                        $this->rowShowButton(true),
                        $this->rowDeleteButton(),
                    ])
                ])
                    ->set('align','center')
                    ->set('fixed','right')
                    ->set('width',100)
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
