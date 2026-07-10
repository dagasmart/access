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
        // $query->where(['device_type' => 'access']); //只查门禁设备
    }

    /**
     * 保存前
     */
    public function saving(&$data, $primaryKey = ''): void
    {
        //        $time = date('H:i');
        //        $sec = toSeconds($time);
        //        dump($sec);
        //        dump(timeToSeconds($time));
        //        dump(secondToTime($sec));
        //        die;
        //        dump($data);
        //        dump($primaryKey);
        $data['allow_date'] = $data['allow_date'] ?? null;
        $data['exclude_date'] = $data['exclude_date'] ?? null;
        $body = [];
        $body['dt_slots'] = $this->arrayToJson(['enable' => boolval($data['exclude_date']), 'data' => $data['exclude_date'], 'key' => 'dt_slot']);
        $body['spt_slots'] = $this->arrayToJson(['enable' => boolval(['allow_date']), 'data' => $data['allow_date'], 'key' => 'ust_slot']);
        $week = $this->arrayToJson(['data' => $data['permission_combo'] ?? null]);
        array_walk($week, function ($item, $key) use (&$body) {
            $body[$key] = $item;
        });
        $data['body'] = $body;
    }

    /**
     * 新增或修改后更新关联数据
     *
     * @param  bool  $isEdit
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
        $model = new EnterpriseService;
        return $model->getEnterpriseAll();
    }

    /**
     * 权限码
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

    public function permissionAll(): array
    {
        $enterprise_id = $this->request->enterprise_id ?? null;

        return $this->query()
            ->where('enterprise_id', $enterprise_id)
            ->orderBy('permission_code')
            ->get(['permission_name as label', 'permission_code as value'])
            ->map(function ($rows) {
                return collect($rows->toArray())->except(['combo'])->all();
            })->toArray();
    }

    /**
     * 时间转换json
     */
    public function arrayToJson(array $rows = []): ?array
    {
        $data = [];
        $key = $rows['key'] ?? null;
        $items = $rows['data'] ?? null;
        $enable = $rows['enable'] ?? null;
        if ($items && is_array($items)) {
            if ($key) {
                $data[$key] = [];
            }

            if (! is_null($enable)) {
                $data['enable'] = $enable;
            }

            foreach ($items as $item) {
                $begin = $item['begin'] ?? null;
                $end = $item['end'] ?? null;
                if ($begin && $end) {
                    $timestamp = $item['date'] ?? null;
                    if (! isTimestamp($timestamp)) {
                        $timestamp = strtotime($timestamp);
                    }
                    if ($timestamp) {
                        if ($key == 'dt_slot') { // 禁止通行
                            $data[$key][] = [
                                's_day' => $timestamp + timeToSeconds($begin),
                                'e_day' => $timestamp + timeToSeconds($end),
                                // 's_day' => $timestamp + timeToSeconds($begin),
                                // 'e_day' => $timestamp + timeToSeconds($end),
                            ];
                        }
                        if ($key == 'ust_slot') { // 允许通行
                            $data[$key][] = [
                                's_sec' => timeToSeconds($begin),
                                'e_sec' => timeToSeconds($end),
                                // 's_sec' => timeToSeconds($begin),
                                // 'e_sec' => timeToSeconds($end),
                            ];
                        }
                    } else {
                        if ($key == 'dt_slot') { // 禁止通行
                            $data[$key][] = [
                                's_day' => date('Ymd', $begin),
                                'e_day' => date('Ymd', $end),
                            ];
                        }
                        if ($key == 'ust_slot') { // 允许通行
                            $data[$key][] = [
                                's_sec' => $begin,
                                'e_sec' => $end,
                            ];
                        }
                    }
                } else {
                    foreach ($item as $k => $value) {
                        $key = str_replace('week', 'st_slots', $k);
                        $data[$key]['st_slot'] = [];
                        if ($value) {
                            foreach ($value as $v) {
                                $begin = $v['begin'] ?? null;
                                $end = $v['end'] ?? null;
                                if ($begin && $end) {
                                    $st_slot = [
                                        's_sec' => timeToSeconds($begin),
                                        'e_sec' => timeToSeconds($end),
                                    ];
                                    $data[$key]['st_slot'][] = $st_slot;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
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
