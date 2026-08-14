<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Enums\Enum;
use DagaSmart\Access\Models\AccessDispatch;
use DagaSmart\Access\Models\AccessUser;
use DagaSmart\Organization\Models\Device;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
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

        $request = request();

        $query->whereHas('user', function ($query) use ($request) {
            $enterprise_id = $request->enterprise_id ?? null;
            $user_name = $request->user_name ?? null;
            $id_card = $request->id_card ?? null;
            $user_type = $request->user_type ?? null;
            $query->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->where('enterprise_id', $enterprise_id);
            })->when($user_name, function ($query) use ($user_name) {
                $query->where('user_name', 'like', "'%$user_name%'");
            })->when($id_card, function ($query) use ($id_card) {
                if (mb_strlen($id_card, 'UTF8') == 4) {
                    $query->where('id_card', 'like', "%$id_card");
                } else {
                    $query->where(['id_card' => $id_card]);
                }
            })->when($user_type, function ($query) use ($user_type) {
                $query->where(['user_type' => $user_type]);
            });
        })->whereHas('device', function ($query) use ($request) {
            $enterprise_id = $request->enterprise_id ?? null;
            $facility_id = $request->facility_id ?? null;
            $device_name = $request->device_name ?? null;
            $device_id = $request->device_id ?? null;
            $query->when($enterprise_id, function ($query) use ($enterprise_id) {
                $query->whereHas('rel', function ($query) use ($enterprise_id) {
                    $query->where('enterprise_id', $enterprise_id);
                });
            })->when($facility_id, function ($query) use ($facility_id) {
                $query->whereHas('rel', function ($query) use ($facility_id) {
                    $query->where('facility_id', $facility_id);
                });
            })->when($device_name, function ($query) use ($device_name) {
                $query->where('device_name', 'like', "'%$device_name%'");
            })->when($device_id, function ($query) use ($device_id) {
                $ids = explode(',', $device_id);
                $query->whereIn('id', $ids);
            });
        });
        // $query->where(['device_type' => 'access']); //只查门禁设备
    }

//    public function list(): array
//    {
//        $list = parent::list();
//        if ($list['items']) {
//            foreach ($list['items'] as &$item) {
//                $item['user_type1111'] = '1111111111111111';
//            }
//        }
//
//        return $list;
//    }

    /**
     * 新增保存
     */
    public function store($data): bool
    {

        $enterpriseId = $data['enterprise_id'] ?? null;
        // ✅ 基础参数校验
        admin_abort_if(! $enterpriseId, '【'.extend_trans('organization.enterprise_name').'】 必选项');

        $userType = $data['user_type'] ?? null;
        admin_abort_if(
            ! in_array($userType, ['student', 'patriarch', 'worker', 'visitor'], true),
            '【用户类型】 必选项'
        );

        $deviceIds = $data['device_id'] ?? null;
        if (is_array($deviceIds)) {
            $deviceIds = array_filter($deviceIds);
        } else {
            $deviceIds = array_filter(explode(',', $deviceIds));
        }
        admin_abort_if(! $deviceIds, '【分发设备】 必选项');

        $gradeId = $data['grade_id'] ?? null;
        $classesId = $data['classes_id'] ?? null;
        $departmentId = $data['department_id'] ?? null;
        $permissionId = $data['permission_id'] ?? 0;

        // ✅ 安全解析 is_boarder，过滤空值
        $isBoarder = $data['is_boarder'] ?? null;
        $isBoarder = $isBoarder !== null && $isBoarder !== ''
            ? array_values(array_filter(explode(',', $isBoarder), fn ($v) => $v !== ''))
            : [];

        $userIds = $data['access_user_id'] ?? null;
        if (blank($userIds)) {
            $model = new AccessUser;
            if ($userType == 'student') {
                admin_abort_if(! $gradeId || ! $classesId, '【年级/班级】 必选项');
                $userIds = $model->query()
                    ->whereHas('student', function (Builder $builder) use ($enterpriseId, $gradeId, $classesId, $isBoarder) {
                        $builder->where('enterprise_id', $enterpriseId)
                            ->where('grade_id', $gradeId)
                            ->when($classesId, fn (Builder $sub) => $sub->where('classes_id', $classesId))
                            ->when($isBoarder, fn (Builder $sub) => $sub->whereIn('is_boarder', $isBoarder));
                    })
                    ->where('user_type', 'student')
                    ->where('state', 1)
                    ->pluck('id')
                    ->toArray();
            }
            if ($userType == 'patriarch') {
                admin_abort_if(! $classesId, '【年级/班级】 必选项');
                $subQuery = EnterpriseGradeClassesStudent::query()
                    ->select('student_id')
                    ->where('enterprise_id', $enterpriseId)
                    ->where('grade_id', $gradeId)
                    ->where('classes_id', $classesId)
                    ->where('state', 1)
                    ->groupBy('student_id');

                $userIds = $model->query()
                    ->whereHas('patriarch', function (Builder $builder) use ($subQuery) {
                        $builder->whereIn('student_id', $subQuery);
                    })
                    ->where('user_type', 'patriarch')
                    ->where('state', 1)
                    ->pluck('id')
                    ->toArray();
            }
            if ($userType == 'worker') {
                admin_abort_if(! $departmentId, '【部门】 必选项');
                $subQuery = EnterpriseDepartmentJobWorker::query()
                    ->select('worker_id')
                    ->where('enterprise_id', $enterpriseId)
                    ->where('department_id', $departmentId)
                    ->whereIn('state', Enum::workerActive())
                    ->groupBy('worker_id');

                $userIds = $model->query()
                    ->whereHas('worker', function (Builder $builder) use ($subQuery) {
                        $builder->whereIn('worker_id', $subQuery);
                    })
                    ->where('user_type', 'worker')
                    ->where('state', 1)
                    ->pluck('id')
                    ->toArray();
            }
            if ($userType == 'visitor') {
                $userIds = $model->query()
                    ->where('user_type', 'visitor')
                    ->where('state', 1)
                    ->orderByDesc('id')
                    ->pluck('id')
                    ->toArray();
            }
        } else {
            $userIds = explode(',', $userIds);
        }

        $module = admin_current_module();
        $merId = admin_mer_id();

        $record = [];

        if ($userIds) {
            foreach ($userIds as $userId) {
                foreach ($deviceIds as $deviceId) {
                    $record[] = [
                        'enterprise_id' => $enterpriseId,
                        'access_user_id' => $userId,
                        'access_device_id' => $deviceId,
                        'access_permission_id' => $permissionId,
                        'state' => 0,
                        'user_type' => $userType,
                        'module' => $module,
                        'mer_id' => $merId,
                    ];
                }
            }
        }

        if (empty($record)) {
            return 0; // 直接返回，避免无效 SQL
        }

        // ✅ 推荐：分块 upsert 必须依赖唯一索引，且 update 字段要完整
        return collect($record)
            ->chunk(500)
            ->map(fn ($chunk) => $this->query()->upsert(
                $chunk->toArray(),
                uniqueBy: ['access_user_id', 'access_device_id', 'access_permission_id', 'module', 'mer_id'],
                update: ['user_type', 'state']
            ))
            ->sum();
    }

    /**
     * 保存前
     */
    public function saving(&$data, $primaryKey = null): void
    {
        $data['device_type'] = 'access'; // 门禁
        $userModel = new AccessUser;
        $userModel->query()
            ->where('enterprise_id', $data['enterprise_id'])
            ->get();
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
                    'to' => admin_url('extension/access/dispatch?enterprise_id='.$res->id.'&enterprise_name='.$res->name),
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
            'img_url' => admin_image_url($row->user->avatar, 800),
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
