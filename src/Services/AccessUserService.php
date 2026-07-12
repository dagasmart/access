<?php

namespace DagaSmart\Access\Services;

use DagaSmart\Access\Models\AccessUser;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
use DagaSmart\Organization\Models\EnterprisePatriarchStudent;
use DagaSmart\Organization\Services\StudentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        $test = 1;
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
    }



    /**
     * 保存前
     */
    public function saving(&$data, $primaryKey = null): void
    {
        $user_type = $data['user_type'] ?? null;
        $enterprise_id = $data['enterprise_id'] ?? null;

        // 身份证号
        $id_card = $data['id_card'] ?? null;
        admin_abort_if(empty($id_card), '请输入有效身份证号');

        if (strpos($id_card, '*')) {
            unset($data['id_card']);
        } else {
            // 身份证号校验
            identifyByIdCard($id_card);
            // 是否已存在
            $id = $data['id'] ?? null;
            $exists = $this->query()
                ->where(['enterprise_id' => $enterprise_id])
                ->where(['id_card' => $id_card])
                ->when($id, function ($query) use ($id) {
                    return $query->where('id', '<>', $id);
                })
                ->where('user_type', $user_type)
                ->exists();
            admin_abort_if($exists, '身份证号(${id_card})已存在，请检查');
        }
    }

    /**
     * 新增或修改后更新关联数据
     *
     * @param  bool  $isEdit
     */
    public function saved($model, $isEdit = false): void
    {
        $request = request()->all();

    }

    /**
     * 单位列表
     */
    public function getEnterpriseAll(): array
    {
        $student = new StudentService;

        return $student->getEnterpriseAll();
    }

    /**
     * 年级列表
     */
    public function getGradeAll(): array
    {
        $student = new StudentService;

        return $student->getGradeAll();
    }

    /**
     * 班级列表
     */
    public function getClassesAll(): array
    {
        $student = new StudentService;

        return $student->getClassesAll();
    }

    public function userAll(): array
    {
        $request = request();

        // 基础参数校验（建议使用 FormRequest 或 validate()）
        $enterpriseId = $request->enterprise_id;
        $gradeId = $request->grade_id;
        $classesId = $request->classes_id;
        $userType = $request->user_type;

        if ($userType == 'student') {

            $isBoarder = explode(',', (string) $request->is_boarder);

            return $this->query()
                ->whereHas('student', function (Builder $builder) use ($enterpriseId, $gradeId, $classesId, $isBoarder) {
                    $builder->where('enterprise_id', $enterpriseId)
                        ->where('grade_id', $gradeId)
                        ->when($classesId, fn (Builder $sub) => $sub->where('classes_id', $classesId))
                        ->when($isBoarder, fn (Builder $sub) => $sub->whereIn('is_boarder', $isBoarder));
                })
                ->with('student')
                ->where('state', 1)
                ->distinct()
                ->get([
                    'id as value',
                    'user_name as label',
                    'user_id',
                    'id_card',
                    // 推荐使用 DB::raw 并明确字段来源，避免 admin_raw 的潜在风险
                    admin_raw("CONCAT(users.user_name, '⟨', users.id_card, '⟩') as label_as"),
                ])
                ->toArray();
        } elseif ($userType == 'patriarch') {
            return [];
        } elseif ($userType == 'worker') {
            return [];
        } elseif ($userType == 'visitor') {
            return [];
        } else {
            return [];
        }
    }

    /**
     * 权限列表
     */
    public function getPermissionAll(): array
    {
        $permission = new AccessPermissionService;

        return $permission->permissionAll();
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

    /**
     * 获取条件用户
     */
    public function getAccessUser(): array|Collection
    {
        $request = request();
        $enterprise_id = $request->enterprise_id;
        $grade_id = $request->grade_id;
        $classes_id = $request->classes_id;
        $department_id = $request->department_id;
        $user_type = $request->user_type;

        if (empty($user_type)) {
            admin_abort('用户类型不能为空');
        }

        if (empty($enterprise_id)) {
            admin_abort(is_school_module().'单位不能为空');
        }

        $record = [];

        if ($user_type === 'student') {

            admin_abort_if(empty($grade_id), '年级不能为空');
            admin_abort_if(empty($classes_id), '班级不能为空');

            $record = EnterpriseGradeClassesStudent::query()
                ->where('enterprise_id', $enterprise_id)
                ->where('grade_id', $grade_id)
                ->where('classes_id', $classes_id)
                ->where('state', 1)
                ->with('student')
                ->get()
                ->map(fn ($item) => [
                    // ...$item->toArray(),
                    'label' => $item->student?->student_name,
                    'value' => $item->student_id,
                ])
                ->collect();
        }

        if ($user_type === 'patriarch') {

            // 1. 入口参数校验（保持原有安全校验）
            admin_abort_if(empty($grade_id), '年级不能为空');
            admin_abort_if(empty($classes_id), '班级不能为空');

            // 2. 构建学生ID子查询（不执行，仅作为SQL片段）
            // ⚠️ 核心：传入Builder对象而非数组，Laravel自动编译为子查询，零PHP内存开销
            $subQuery = EnterpriseGradeClassesStudent::query()
                ->select('student_id')
                ->where('enterprise_id', $enterprise_id)
                ->where('grade_id', $grade_id)
                ->where('classes_id', $classes_id)
                ->where('state', 1)
                ->groupBy('student_id'); // groupBy 替代 distinct，避免临时表排序开销

            // 3. 主查询：通过子查询关联 + 预加载家长信息
            $record = EnterprisePatriarchStudent::query()
                ->where('enterprise_id', $enterprise_id)
                ->whereIn('student_id', $subQuery) // 安全：生成 IN (SELECT ...) 而非 IN (1,2,3...)
                ->with('patriarch') // 预加载，杜绝 N + 1 问题
                ->get()
                ->map(fn ($item) => [
                    // ...$item->toArray(),
                    'label' => $item->patriarch?->patriarch_name,
                    'value' => $item->patriarch_id,
                ])
                ->collect();

        }

        if ($user_type == 'worker') {
            admin_abort_if(empty($department_id), '部门不能为空');
            $record = EnterpriseDepartmentJobWorker::query()
                ->where('enterprise_id', $enterprise_id)
                ->where('department_id', $department_id)
                ->whereIn('state', [1, 2, 3, 4])
                ->with('worker')
                ->get()
                ->map(fn ($item) => [
                    // ...$item->toArray(),
                    'label' => $item->worker?->worker_name,
                    'value' => $item->worker_id,
                ])
                ->collect();
        }

        return $record;
    }

    /**
     * 一键导入
     */
    public function userImport(): ?bool
    {
        $request = request();
        $user_type = $request->user_type;
        $enterprise_id = $request->enterprise_id;
        $grade_id = $request->grade_id;
        $classes_id = $request->classes_id;
        $department_id = $request->department_id;
        $user_id = $request->user_id;
        $open_type = $request->open_type;

        if (empty($user_type)) {
            admin_abort('用户类型不能为空');
        }

        if (empty($enterprise_id)) {
            admin_abort(is_school_module().'单位不能为空');
        }

        // 最大排序值
        $max = (int) $this->query()->where('enterprise_id', $enterprise_id)->max('sort');

        $record = [];
        if ($user_type === 'student') {
            // 1. 入口参数校验（保持原有安全校验）
            admin_abort_if(empty($grade_id), '年级不能为空');
            admin_abort_if(empty($classes_id), '班级不能为空');

            $record = EnterpriseGradeClassesStudent::query()
                ->where('enterprise_id', $enterprise_id)
                ->where('grade_id', $grade_id)
                ->where('classes_id', $classes_id)
                ->where('state', 1)
                ->when($user_id, function (Builder $query) use ($user_id) {
                    $query->whereIn('student_id', explode(',', (string) $user_id));
                })
                ->with('student')
                ->get()
                ->map(fn ($item, $index) => [
                    'user_id' => $item->student?->id,
                    'user_name' => $item->student?->student_name,
                    'user_avatar' => $item->student?->avatar,
                    'user_type' => $user_type,
                    'id_card' => base64_decode($item->student?->id_card_enc),
                    'mobile' => $item->student?->mobile,
                    'enterprise_id' => $item->enterprise_id,
                    'open_type' => $open_type,
                    'state' => 1,
                    'sort' => intval($max + $index + 1),
                    'id_card_enc' => $item->student?->id_card_enc,
                    'module' => $item->module,
                    'mer_id' => $item->mer_id,
                ]);
        }

        if ($user_type === 'patriarch') {
            // 1. 入口参数校验（保持原有安全校验）
            admin_abort_if(empty($grade_id), '年级不能为空');
            admin_abort_if(empty($classes_id), '班级不能为空');

            // 2. 构建学生ID子查询（不执行，仅作为SQL片段）
            // ⚠️ 核心：传入Builder对象而非数组，Laravel自动编译为子查询，零PHP内存开销
            $subQuery = EnterpriseGradeClassesStudent::query()
                ->select('student_id')
                ->where('enterprise_id', $enterprise_id)
                ->where('grade_id', $grade_id)
                ->where('classes_id', $classes_id)
                ->where('state', 1)
                ->groupBy('student_id'); // groupBy 替代 distinct，避免临时表排序开销

            // 3. 主查询：通过子查询关联 + 预加载家长信息
            $record = EnterprisePatriarchStudent::query()
                ->where('enterprise_id', $enterprise_id)
                ->whereIn('student_id', $subQuery) // 安全：生成 IN (SELECT ...) 而非 IN (1,2,3...)
                ->with('patriarch') // 预加载，杜绝 N + 1 问题
                ->get()
                ->map(fn ($item, $index) => [
                    'user_id' => $item->patriarch?->id,
                    'user_name' => $item->patriarch?->patriarch_name,
                    'user_avatar' => $item->patriarch?->avatar,
                    'user_type' => $user_type,
                    'id_card' => base64_decode($item->patriarch?->id_card_enc),
                    'mobile' => $item->patriarch?->mobile,
                    'enterprise_id' => $item->enterprise_id,
                    'open_type' => $open_type,
                    'state' => 1,
                    'sort' => intval($max + $index + 1),
                    'id_card_enc' => $item->patriarch?->id_card_enc,
                    'module' => $item->module,
                    'mer_id' => $item->mer_id,
                ]);

        }

        if ($user_type === 'worker') {
            // 1. 入口参数校验（保持原有安全校验）
            admin_abort_if(empty($department_id), '部门不能为空');

            $record = EnterpriseDepartmentJobWorker::query()
                ->where('enterprise_id', $enterprise_id)
                ->where('department_id', $department_id)
                ->whereIn('state', [1, 2, 3, 4])
                ->with('worker')
                ->get()
                ->map(fn ($item, $index) => [
                    'user_id' => $item->worker?->id,
                    'user_name' => $item->worker?->worker_name,
                    'user_avatar' => $item->worker?->avatar,
                    'user_type' => $user_type,
                    'id_card' => base64_decode($item->worker?->id_card_enc),
                    'mobile' => $item->worker?->mobile,
                    'enterprise_id' => $item->enterprise_id,
                    'open_type' => $open_type,
                    'state' => 1,
                    'sort' => intval($max + $index + 1),
                    'id_card_enc' => $item->worker?->id_card_enc,
                    'module' => $item->module,
                    'mer_id' => $item->mer_id,
                ]);
        }

        return admin_transaction(function () use ($record) {
            $this->query()->upsert(
                $record->toArray(),
                uniqueBy: ['user_id', 'user_type', 'enterprise_id', 'module', 'mer_id'], // 冲突判断字段
                update: ['id_card', 'mobile', 'open_type', 'state'] // 冲突时更新的字段
            );

            return true;
        });

    }
}
