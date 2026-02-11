<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Models\AccessDispatch;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Database\Eloquent\Builder;


/**
 * 门禁设备-服务类
 *
 * @method AccessDispatch getModel()
 * @method AccessDispatch|Builder query()
 */
class AccessDispatchService extends AdminService
{
	protected string $modelName = AccessDispatch::class;

    public function loadRelations($query): void
    {
        $query->with(['user', 'device']);
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

        $query->whereHas('user', function ($query) {
            $enterprise_id = request('enterprise_id');
            $user_name = request('user_name');
            $id_card = request('id_card');
            $user_type = request('user_type');
            $query->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->where('enterprise_id', $enterprise_id);
            })->when($user_name, function ($query) use ($user_name) {
                $query->where('user_name', 'like', "%$user_name%");
            })->when($id_card, function ($query) use ($id_card) {
                if (mb_strlen($id_card, 'UTF8') == 4) {
                    $query->where('id_card', 'like', "%$id_card");
                } else {
                    $query->where(['id_card' => $id_card]);
                }
            })->when($user_type, function ($query) use ($user_type) {
                $query->where(['user_type' => $user_type]);
            });
        })->whereHas('device', function ($query) {
            $enterprise_id = request('enterprise_id');
            $facility_id = request('facility_id');
            $device_name = request('device_name');
            $device_sn = request('device_sn');
            $query->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->whereHas('rel', function ($query) use ($enterprise_id) {
                    $query->where('enterprise_id', $enterprise_id);
                });
            })->when($facility_id, function ($query) use ($facility_id) {
                $query->whereHas('rel', function ($query) use ($facility_id) {
                    $query->where('facility_id', $facility_id);
                });
            })->when($device_name, function ($query) use ($device_name) {
                $query->where('device_name', 'like', "%$device_name%");
            })->when($device_sn, function ($query) use ($device_sn) {
                $query->where(['device_sn' => $device_sn]);
            });
        });
        //$query->where(['device_type' => 'access']); //只查门禁设备
    }

    /**
     * 保存前
     * @param $data
     * @param $primaryKey
     * @return void
     */
    public function saving(&$data, $primaryKey = null): void
    {
        $data['device_type'] = 'access'; //门禁
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
        $request = request()->all();
        if ($model->id && !empty($request['enterprise_id']) && !empty($request['facility_id'])) {
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

    public static function getNavList()
    {
        return Enterprise::query()
            ->where('state', 1)
            ->orderBy('id')
            ->get(['id', 'enterprise_name as name'])
            ->map(function ($res) {
                return [
                    'label' => $res->name,
                    'value' => $res->id,
                    'to'    => admin_url('biz/access/dispatch?enterprise_id=' . $res->id.'&enterprise_name=' . $res->name),
                ];
            })
            ->toArray();
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
     * 递归选择项
     * @return array
     */
    public function getFacilityAll(): array
    {
        $id = request('id');
        $enterprise_id = request('enterprise_id');
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
