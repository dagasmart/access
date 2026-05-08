<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Models\AccessUser;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Services\EnterpriseService;
use Illuminate\Database\Eloquent\Builder;


/**
 * 门禁用户-服务类
 *
 * @method AccessUser getModel()
 * @method AccessUser|Builder query()
 */
class AccessUserService extends AdminService
{
	protected string $modelName = AccessUser::class;

    public function loadRelations($query): void
    {
        $query->with('rel');
//        $query->whereHas('rel', function ($query) {
//            $mer_id = admin_mer_id();
//            $query->where('module', admin_current_module())
//                ->when($mer_id, function ($query) use ($mer_id) {
//                    $query->where('mer_id', $mer_id);
//                });
//        })->with(['rel']);
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
        //$query->where(['device_type' => 'access']); //只查门禁设备
    }

    public function list(): array
    {
        $list = parent::list();
        if ($list['items']) {
            foreach ($list['items'] as &$item) {
                if ($item['user_type'] == 'patriarch') {
                    $item['rel'] = 111111111111;
                }
                $item['rel'] = 111111111111;
            }
        }
        return $list;
    }

    /**
     * 保存前
     * @param $data
     * @param $primaryKey
     * @return void
     */
    public function saving(&$data, $primaryKey = null): void
    {
        $enterprise_id = $data['enterprise_id'] ?? null;
        //身份证号
        admin_abort_if(empty($data['id_card']), '请输入有效身份证号');
        $id_card = $data['id_card'] ?? null;
        if ($id_card) {
            if (strpos($id_card, '*')) {
                unset($data['id_card']);
            } else {
                //身份证号校验
                identifyByIdCard($id_card);
                //是否已存在
                $id = $data['id'] ?? null;
                $exists = $this->getModel()::query()
                    ->where(['enterprise_id' => $enterprise_id])
                    ->where(['id_card' => $id_card])
                    ->when($id, function ($query) use ($id) {
                        return $query->where('id', '<>', $id);
                    })
                    ->exists();
                admin_abort_if($exists, '身份证号(${id_card})已存在，请检查');
            }
        }
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

    /**
     * 单位列表
     */
    public function getEnterpriseAll(): array
    {
        $student = new \DagaSmart\Organization\Services\StudentService;
        return $student->getEnterpriseAll();
    }

    /**
     * 年级列表
     */
    public function getGradeAll(): array
    {
        $student = new \DagaSmart\Organization\Services\StudentService;
        return $student->getGradeAll();
    }

    /**
     * 班级列表
     * @return array
     */
    public function getClassesAll(): array
    {
        $student = new \DagaSmart\Organization\Services\StudentService;
        return $student->getClassesAll();
    }

    public function userAll(): array
    {
        $enterprise_id = request('enterprise_id');
        $grade_id = request('grade_id');
        $classes_id = request('classes_id');
        $user_type = request('user_type');
        if ($user_type == 'student') {
            $is_boarder = request('is_boarder');

            return $this->query()
                ->whereHas('student', function ($query) use ($enterprise_id, $grade_id, $classes_id, $is_boarder) {
                    $query
                        ->where('enterprise_id', $enterprise_id)
                        ->where('grade_id', $grade_id)
                        ->when($classes_id, function ($query) use ($classes_id) {
                            $query->where('classes_id', $classes_id);
                        })
                        ->when(!is_null($is_boarder), function ($query) use ($is_boarder) {
                            $query->where('is_boarder', $is_boarder);
                        });
                })
                ->with('student')
                ->where('state', 1)
                ->get(['id as value', 'user_name as label', 'user_id', 'id_card', admin_raw("concat(user_name,'⟨',id_card,'⟩') as label_as")])
                ->toArray();
        }
        return [];
    }

    /**
     * 权限列表
     * @return array
     */
    public function getPermissionAll(): array
    {
        $permission = new \DagaSmart\Access\Services\AccessPermissionService;
        return $permission->permissionAll();
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
