<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\Access\Services\AccessLogService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;

class AccessLogController extends AdminController
{
    protected string $serviceName = AccessLogService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton('dialog', 250),
                ...$this->baseHeaderToolBar(),
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
                amis()->TableColumn('user.user_avatar', '照片')
                    ->set('type', 'avatar')
                    ->set('src', '${user.user_avatar}')
                    ->set('size', 'small')
                    ->set('fixed', 'left'),
                amis()->TableColumn('user_name', '用户姓名')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                    ])
                    ->set('fixed', 'left'),
                amis()->TableColumn('device_pos', '行为事件')
                    ->searchable([
                        'name' => 'device_pos',
                        'type' => 'select',
                        'options' => [['label' => '进口入场', 'value' => 'in'], ['label' => '出口离场', 'value' => 'out']],
                    ])
                    ->set('type', 'mapping')
                    ->set('map', [
                        'in' => '<span class="label label-success rounded-full border">进口入场</span>',
                        'out' => '<span class="label label-danger rounded-full border">出口离场</span>',
                    ]),
                amis()->TableColumn('scene_photo', '现场实拍')
                    ->set('type', 'static-image')
                    ->set('src', '${scene_photo}')
                    ->set('width', 30)
                    ->set('height', 30)
                    ->set('align', 'center')
                    ->set('enlargeAble', true)
                    ->set('enlargeWithGallary', false)
                    ->set('showToolbar', true)
                    ->set('enlargeTitle', '现场实拍'),
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
                amis()->TableColumn('rel.device.device_name', '设备信息')
                    ->searchable([
                        'name' => 'device_sn',
                        'type' => 'input-text',
                        'placeholder' => '请输入设备编码',
                    ])
                    ->set('type', 'tpl')
                    ->set('tpl', '${rel.device.device_name}<h5 class="m-0 mt-1 text-secondary">${rel.device.device_sn}</h5>')
                    ->width(200),
                amis()->TableColumn('created_at', '发生时间')
                    ->sortable()
                    ->set('type', 'datetime')
                    ->set('fixed', 'right')
                    ->width(150),
                $this->rowActions([
                    amis()->Operation()->label(admin_trans('admin.actions'))->buttons([
                        $this->rowShowButton(true),
                        $this->rowDeleteButton(),
                    ]),
                ])
                    ->set('align', 'center')
                    ->set('fixed', 'right')
                    ->set('width', 100),
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([
            amis()->Tabs()->tabsMode('line')->tabs([
                amis()->Tab()->title('记录信息')->icon('menu')->body([
                    amis()->GroupControl()->mode('horizontal')->body([
                        amis()->GroupControl()->direction('vertical')->body([
                            amis()->StaticExactControl('user_id', 'ID')->visibleOn('${id}'),
                            amis()->StaticExactControl('user_name', '用户'),
                            amis()->StaticExactControl('rel.enterprise.enterprise_name', '机构单位'),
                            amis()->StaticExactControl('rel.facility.facility_name', '设施主体'),
                            amis()->StaticExactControl('rel.device.device_name', '设备名称'),
                            amis()->StaticExactControl('rel.device.device_sn', '设备编码'),
                            amis()->StaticExactControl('created_at', '发生时间'),
                        ]),
                        amis()->GroupControl()->body([
                            amis()->Image()->src('${scene_photo}')
                                ->className('bg-current rounded-lg')
                                ->thumbMode('contain')
                                ->enlargeAble()
                                ->width('300px')
                                ->height('350px'),

                        ]),
                    ]),

                ]),
            ]),
        ])->static();
    }
}
