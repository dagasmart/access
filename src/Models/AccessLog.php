<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-进出记录表
 */
class AccessLog extends Model
{
    protected $table = 'biz_access_log';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public function getScenePhotoAttribute($value): ?string
    {
        return admin_image_url($value);
    }

    public function rel(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class, 'device_id', 'device_id')->with(['enterprise', 'facility', 'device']);
    }

    public function user(): HasOne
    {
        return $this->hasOne(AccessUser::class, 'user_id', 'user_id');
    }
}
