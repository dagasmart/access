<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Services\AccessDeviceService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;


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
                amis()->TableColumn('facility_id', '设施主体')
                    ->searchable([
                        'name' => 'facility_id',
                        'type' => 'tree-select',
                        'multiple' => true,
                        'options' => $this->service->options(),
                    ])
                    ->set('type', 'tpl')
                    ->set('options', $this->service->options())
                    ->set('value', '${rel.facility.id}')
                    ->set('enableNodePath', true)
                    ->set('static', true)
                    ->width(150),
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
                        $this->rowShowButton(true,250),
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


}
