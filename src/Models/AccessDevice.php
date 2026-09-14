<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Basic\Models\OrganizationFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-设备表
 */
class AccessDevice extends Model
{
    protected $table = 'biz_device';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public function getImagesAttribute($value): ?array
    {
        return admin_images_url($value);
    }

    public function setImagesAttribute($value): void
    {
        $this->attributes['images'] = admin_images_path($value);
    }

    public function rel(): HasOne
    {
        return $this->hasOne(OrganizationFacilityDevice::class, 'device_id', 'id')->with(['organization', 'facility']);
    }

    public function organization(): HasOne
    {
        return $this->hasOne(OrganizationFacilityDevice::class,
            'device_id',
            'id'
        )->with(['organization']);
    }
}
