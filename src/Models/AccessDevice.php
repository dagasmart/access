<?php

namespace DagaSmart\Access\Models;

use DagaSmart\School\Models\Model;
use DagaSmart\School\Models\SchoolFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学生表
 */
class AccessDevice extends Model
{

	protected $table = 'biz_device';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(SchoolFacilityDevice::class,'device_id','id')->with(['school','facility']);
    }

    public function school(): HasOne
    {
        return $this->hasOne(SchoolFacilityDevice::class,
            'device_id',
            'id'
            )->with(['school']);
    }

}
