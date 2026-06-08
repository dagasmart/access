<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-设备表
 */
class AccessDevice extends Model
{
    protected $table = 'biz_device';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class, 'device_id', 'id')->with(['enterprise', 'facility']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class,
            'device_id',
            'id'
        )->with(['enterprise']);
    }
}
