<?php

namespace DagaSmart\Access\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use DagaSmart\Basic\Models\Organization;
use DagaSmart\Basic\Models\OrganizationDepartmentJobWorker;
use DagaSmart\Basic\Models\OrganizationGradeClassesStudent;
use DagaSmart\Basic\Models\OrganizationPatriarchStudent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-用户表
 */
class AccessUser extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;
    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    protected $table = 'biz_access_user';

    protected $primaryKey = 'id';

    protected $hidden = ['module', 'mer_id'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    protected $appends = ['rel'];

    /**
     * 身份证号脱敏
     */
    public function getIdCardAttribute($value): ?string
    {
        return admin_sensitive($value, 6, 8) ?? null;
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    /**
     * 手机号脱敏
     */
    public function getMobileAttribute($value): ?string
    {
        return admin_sensitive($value, 3, 5) ?? null;
    }

    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setAvatarAttribute($value): void
    {
        $this->attributes['avatar'] = admin_image_path($value);
    }

    // 动态访问器（仅用于已加载模型的属性访问）
    public function getRelAttribute()
    {
        return match ($this->user_type) {
            'student' => $this->student,
            'patriarch' => $this->patriarch,
            'worker' => $this->worker,
            'visitor' => $this->visitor,
            default => null,
        };
    }

    public function student(): HasOne
    {
        return $this->hasOne(OrganizationGradeClassesStudent::class, 'student_id', 'user_id')
            ->with(['organization', 'grade', 'classes']);
    }

    public function patriarch(): HasOne
    {
        return $this->hasOne(OrganizationPatriarchStudent::class, 'patriarch_id', 'user_id')
            ->with(['organization', 'patriarch']);
    }

    public function worker(): HasOne
    {
        return $this->hasOne(OrganizationDepartmentJobWorker::class, 'worker_id', 'user_id')
            ->with(['organization', 'department']);
    }

    public function visitor(): HasOne
    {
        return $this->hasOne(Organization::class, 'id', 'organization_id');
    }
}
