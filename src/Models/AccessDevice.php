<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\Model;
use DagaSmart\Organization\Models\EnterPriseFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁设备模型
 */
class AccessDevice extends Model
{

	protected $table = 'biz_device';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(EnterPriseFacilityDevice::class,'device_id','id')->with(['enterprise','facility']);
    }

    public function enterprise(): HasOne
    {
        return $this->hasOne(EnterPriseFacilityDevice::class,
            'device_id',
            'id'
            )->with(['enterprise']);
    }

}
