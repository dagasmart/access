<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Basic\Models\OrganizationFacilityDevice;
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
        return $this->hasOne(OrganizationFacilityDevice::class, 'device_id', 'device_id')->with(['organization', 'facility', 'device']);
    }

    public function user(): HasOne
    {
        return $this->hasOne(AccessUser::class, 'user_id', 'user_id');
    }
}
