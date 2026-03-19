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

    public function getIdCardAttribute($value): string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && !strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

    public function getUserAvatarAttribute($value): ?string
    {
        return admin_image_url($value, 800);
    }

    public function setUserAvatarAttribute($value): void
    {
        $this->attributes['user_avatar'] = admin_image_path($value, 800);
    }

    public function student(): hasOne
    {
        return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id');
    }

    public function rel(): hasOne
    {
        if ($this->user_type == 'worker') {
            return $this->hasOne(Worker::class, 'id', 'worker_id');
        } else {
            return $this->hasOne(EnterpriseGradeClassesStudent::class, 'student_id', 'user_id')->with(['enterprise', 'grade', 'classes']);
        }
    }



}
