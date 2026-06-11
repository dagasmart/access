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
        $query->whereHas('rel');
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
        parent::searchable($query);
        $query->where(['device_type' => 'access']); // 只查门禁设备
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
        if ($model->id && ! empty($request['enterprise_id']) && ! empty($request['facility_id'])) {
            $data = [
                'enterprise_id' => $request['enterprise_id'],
                'facility_id' => $request['facility_id'],
                'device_id' => $model->id,
            ];
            admin_transaction(function () use ($data) {
                if ($data['device_id']) {
                    EnterpriseFacilityDevice::query()->where($data)->delete();
                }
                EnterpriseFacilityDevice::query()->insert($data);
            });
        }
    }

    /**
     * 机构单位列表
     */
    public function getEnterpriseAll(): array
    {
        $model = new EnterpriseService;

        return $model->query()
            ->select(['id as value', 'enterprise_name as label', 'id'])
            ->get()
            ->toArray();
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
