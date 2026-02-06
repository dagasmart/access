<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Enums\Enum;
use DagaSmart\Access\Models\AccessPermission;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Database\Eloquent\Builder;


/**
 * 门禁-设备服务类
 *
 * @method AccessPermission getModel()
 * @method AccessPermission|Builder query()
 */
class AccessPermissionService extends AdminService
{
	protected string $modelName = AccessPermission::class;

    public function loadRelations($query): void
    {
        $query->whereHas('rel', function ($query) {
            $mer_id = admin_mer_id();
            $query->where('module', admin_current_module())
                ->when($mer_id, function ($query) use ($mer_id) {
                    $query->where('mer_id', $mer_id);
                });
        })->with(['rel']);
    }

    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy('enterprise_id', 'asc');
        }
        $query->orderBy('permission_code', 'asc');
    }

    public function searchable($query): void
    {
        parent::searchable($query);
        //$query->where(['device_type' => 'access']); //只查门禁设备
    }

    /**
     * 新增或修改后更新关联数据
     * @param $model
     * @param bool $isEdit
     * @return void
     */
    public function saved($model, $isEdit = false): void
    {
        parent::saved($model, $isEdit);
//        $request = request()->all();
//        $data = [
//            'enterprise_id' => $request['enterprise_id'],
//            'facility_id' => $request['facility_id'],
//            'device_id' => $model->id,
//        ];
//        admin_transaction(function () use ($data) {
//            if ($data['device_id']) {
//                EnterpriseFacilityDevice::query()->where($data)->delete();
//            }
//            EnterpriseFacilityDevice::query()->insert($data);
//        });
    }

    /**
     * 机构单位列表
     */
    public function getEnterpriseAll(): array
    {
        return (new EnterpriseService)->query()
            ->select(['id as value', 'enterprise_name as label', 'id'])
            ->get()
            ->toArray();
    }

    /**
     * 权限码
     * @return array
     */
    public function permissionCode(): array
    {
        $data = Enum::PERMISSION_CODE ?? [];
        $enterprise_id = $this->request->enterprise_id ?? null;
        $id = $this->request->id ?? null;
        if ($data && $enterprise_id) {
            $pluck = $this->query()
                ->where('enterprise_id', $enterprise_id)
                ->when($id, function ($query) use ($id) {
                    $query->where('id', '<>', $id);
                })
                ->pluck('permission_code')
                ->toArray();
            array_walk($data, function (&$item) use ($pluck) {
                if ($pluck) {
                $item['hidden'] = in_array($item['value'], $pluck);
                }
            });
        }
        return $data;
    }

    /**
     * 递归选择项
     * @return array
     */
    public function options(): array
    {
        $id = request()->id;
        $enterprise_id = request()->enterprise_id;
        $data = $this->query()->from('biz_facility as a')
            ->join('biz_enterprise_facility as b','a.id','=','b.facility_id')
            ->select(['a.id as value', 'a.facility_name as label', 'a.id', 'a.parent_id'])
            ->when($enterprise_id, function($query) use ($enterprise_id) {
                $query->where('b.enterprise_id', $enterprise_id);
            })
            ->when($id, function($query) use ($id) {
                $query->where('b.facility_id', '<>', $id);
            })
            ->get()
            ->toArray();
        return array2tree($data);
    }

}
