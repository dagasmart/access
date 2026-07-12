<?php

namespace DagaSmart\Access\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\EnterpriseDepartmentJobWorker;
use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
use DagaSmart\Organization\Models\EnterprisePatriarchStudent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-用户表
 */
class AccessUser extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_access_user';

    protected $primaryKey = 'id';

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    protected $hidden = ['module', 'mer_id'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    protected $appends = ['rel'];

    public function getIdCardAttribute($value): string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    public function getUserAvatarAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setUserAvatarAttribute($value): void
    {
        $this->attributes['user_avatar'] = admin_image_path($value);
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
        return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')
            ->with(['enterprise', 'grade', 'classes']);
    }

    public function patriarch(): HasOne
    {
        return $this->hasOne(EnterprisePatriarchStudent::class, 'patriarch_id', 'user_id')
            ->with(['enterprise', 'patriarch']);
    }

    public function worker(): HasOne
    {
        return $this->hasOne(EnterpriseDepartmentJobWorker::class, 'worker_id', 'user_id')
            ->with(['enterprise', 'department']);
    }

    public function visitor(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id');
    }

}
