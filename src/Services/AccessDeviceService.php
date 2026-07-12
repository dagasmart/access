<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Models\AccessDevice;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Database\Eloquent\Builder;

/**
 * 门禁设备-服务类
 *
 * @method AccessDevice getModel()
 * @method AccessDevice|Builder query()
 */
class AccessDeviceService extends AdminService
{
    protected string $modelName = AccessDevice::class;

    public function loadRelations($query): void
    {
        $enterprise_id = request()->enterprise_id ?? null;
        $facility_id = request()->facility_id ?? null;

        $query->whereHas('rel', function (Builder $builder) use ($enterprise_id, $facility_id) {
            $builder
                ->when($enterprise_id, function ($query) use ($enterprise_id) {
                    $query->where('enterprise_id', $enterprise_id);
                })
                ->when($facility_id, function ($query) use ($facility_id) {
                    $query->where('facility_id', explode(',', (string) $facility_id));
                });
        })->with(['rel']);
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy($this->primaryKey(), 'asc');
        }
    }

    public function searchable($query): void
    {
        $query->where(['device_type' => 'access']); // 只查门禁设备

        parent::searchable($query);
    }

    /**
     * 保存前
     */
    public function saving(&$data, $primaryKey = null): void
    {
        $data['device_type'] = 'access'; // 门禁
    }

    /**
     * 新增或修改后更新关联数据
     *
     * @param  bool  $isEdit
     */
    public function saved($model, $isEdit = false): void
    {
        parent::saved($model, $isEdit);

        $request = request()->all();

        $enterprise_id = $request['enterprise_id'];
        $facility_id = $request['facility_id'];

        if ($model->id && ! empty($enterprise_id) && ! empty($facility_id)) {
            // 如果device_id只能关联一条记录，应该以 device_id 作为查找条件
            $priKey = [
                'device_id' => $model->id,
            ];
            // 更新/创建的数组
            $values = [
                'enterprise_id' => $enterprise_id,
                'facility_id' => $facility_id,
                'module' => admin_current_module(),
                'mer_id' => admin_mer_id(),
            ];

            admin_transaction(function () use ($priKey, $values) {
                // 如果记录已存在则更新，不存在则创建
                // 前提：数据库有对应的联合唯一索引
                EnterpriseFacilityDevice::updateOrCreate(
                    $priKey,  // 查找条件
                    $values // 更新/创建的值
                );
            });
        }
    }

    /**
     * 机构单位列表
     */
    public function getEnterpriseAll(): array
    {
        $model = new EnterpriseService;

        return $model->getEnterpriseAll();
    }

    /**
     * 设备列表
     */
    public function deviceAll(): array
    {
        $model = new EnterpriseFacilityDevice;

        return $model->with('device')
            ->get()
            ->toArray();
    }

    /**
     * 递归选择项
     */
    public function options(): array
    {
        $id = request()->id;
        $enterprise_id = request()->enterprise_id;

        if (empty($enterprise_id)) {
            return [];
        }

        $data = $this->query()->from('biz_facility as a')
            ->join('biz_enterprise_facility as b', 'a.id', '=', 'b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->where('b.enterprise_id', $enterprise_id);
            })
            ->when($id, function ($query) use ($id) {
                $query->where('b.facility_id', '<>', $id);
            })
            ->get()
            ->toArray();

        return array2tree($data);
    }
}
