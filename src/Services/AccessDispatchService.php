<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Models\AccessDispatch;
use DagaSmart\Organization\Models\Device;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Database\Eloquent\Builder;
use PhpMqtt\Client\Facades\MQTT;

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
            $device_id = request('device_id');
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
            })->when($device_id, function ($query) use ($device_id) {
                $ids = explode(',', $device_id);
                $query->whereIn('id', $ids);
            });
        });
        // $query->where(['device_type' => 'access']); //只查门禁设备
    }

    public function list(): array
    {
        $list = parent::list();
        if ($list['items']) {
            foreach ($list['items'] as &$item) {
                $item['user_type1111'] = '1111111111111111';
            }
        }

        return $list;
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
     * 左侧菜单栏
     */
    public static function getNavList(): array
    {
        return Enterprise::query()
            ->whereHas('bind')
            ->where('state', 1)
            ->orderBy('id')
            ->get(['id', 'enterprise_name as name'])
            ->map(function ($res) {
                return [
                    'label' => $res->name,
                    'value' => $res->id,
                    'to' => admin_url('biz/access/dispatch?enterprise_id='.$res->id.'&enterprise_name='.$res->name),
                    'active' => $res->id === (int) request('enterprise_id'),
                    'activeOn' => $res->id === (int) request('enterprise_id'),
                ];
            })
            ->toArray();
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
     * 递归选择项
     */
    public function getFacilityAll(): array
    {
        $id = request('id');
        $enterprise_id = request('enterprise_id');
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

    /**
     * 设备列表
     */
    public function getDeviceAll(): array
    {
        $model = new Device;

        return $model->query()
            ->select(['id as value', 'device_name as label', 'id'])
            ->where(['device_type' => 'access'])
            ->get()
            ->toArray();
    }

    /**
     * 下发至设备
     */
    public function userFacePublish(): bool
    {
        $id = request('id');
        admin_abort_if(! $id, 'id不能为空');
        $row = $this->query()->with('user', 'device')->where('id', $id)->first();
        //        dump($row->toArray());die;
        if (! $row) {
            admin_abort('用户不存在');
        }
        if (! $row->user) {
            admin_abort('用户基础数据不存在');
        }
        if (! $row->device) {
            admin_abort('门禁设备数据不存在');
        }
        // 创建注册人员
        //        $data = [
        //            'client_id' => 'f3631cb0-a66a5c60',
        //            'version' => '0.2',
        //            'cmd' => 'create_face',
        //            'per_id' => $row->user->user_id,
        //            'face_id' => $row->user->user_id,
        //            'per_name' => $row->user->user_name,
        //            'idcardNum' => base64_decode($row->user->id_card_enc),
        //            'img_data' => '',
        //            'img_url' => 'http://bjylt.oss-cn-chengdu.aliyuncs.com/image/2026-01/15/520327201101030145.jpg',
        //            'idcardper' => base64_decode($row->user->id_card_enc),
        //            's_time' => time(),
        //            'e_time' => strtotime('+1 year'),
        //            'per_type' => 0, //名单类型	0-白名单 2-黑名单
        //            'usr_type' => 1, //权限组 0,1,2,3,4,5
        //            'auth_type' => 0,
        //            'auth_type_name' => 'c2NobWlkdA==',
        //            'dscode_img' => 'fffffff'
        //        ];

        $data = [
            'client_id' => strval($row->device->device_sn),
            'version' => '0.2',
            'cmd' => 'create_face',
            'per_id' => strval($row->user->user_id),
            'face_id' => strval($row->user->user_id),
            'per_name' => strval($row->user->user_name),
            'idcardNum' => base64_decode($row->user->id_card_enc),
            'img_data' => '',
            'img_url' => admin_image_url($row->user->user_avatar, 800),
            'idcardper' => base64_decode($row->user->id_card_enc),
            's_time' => time(),
            'e_time' => strtotime('+1 year'),
            'per_type' => 0, // 名单类型	0-白名单 2-黑名单
            'usr_type' => 1, // 权限组 0,1,2,3,4,5
            'auth_type' => 0,
            'auth_type_name' => 'c2NobWlkdA==',
            'dscode_img' => 'fffffff',
        ];
        // f3631cb0-a66a5c60
        // 495462f0-0c7e176d
        if ($this->userFaceDelete($row->device->device_sn, $row->user->user_id)) {
            if ($this->runPublish($row->device->device_sn, $data)) {
                return true;
            }
        }

        return false;
    }

    //
    public function userFaceDelete($device_sn = null, $perId = null): bool
    {
        $per_id = request('per_id', $perId);
        if ($per_id) {
            $data = [
                'client_id' => strval($device_sn),
                'version' => '0.2',
                'cmd' => 'delete_face',
                'per_id' => strval($per_id),
                'type' => 0,
            ];

            return $this->runPublish($device_sn, $data);
        }

        return false;
    }

    public function runPublish($device_sn, array $data = []): bool
    {
        try {
            $topic = "face/{$device_sn}/request";
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            MQTT::publish($topic, $json);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
