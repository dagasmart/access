<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\EnterpriseGradeClassesStudent;
use DagaSmart\Organization\Models\Worker;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * 门禁-用户表
 */
class AccessUser extends Model
{

	protected $table = 'biz_access_user';
    protected $primaryKey = 'id';

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    public function getUserAvatarAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function setUserAvatarAttribute($value): void
    {
        $this->attributes['user_avatar'] = admin_image_path($value);
    }

    public function rel(): hasOne
    {
        if ($this->user_type == 'worker') {
            return $this->hasOne(Worker::class, 'id', 'worker_id');
        } elseif ($this->user_type == 'student') {
            return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')->with(['enterprise', 'grade', 'classes']);
        } elseif ($this->user_type == 'patriarch') {
            return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')->with(['enterprise', 'grade', 'classes']);
        } elseif ($this->user_type == 'visitor') {
            return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')->with(['enterprise', 'grade', 'classes']);
        } else {
            return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')->with(['enterprise', 'grade', 'classes']);
        }
    }

}
