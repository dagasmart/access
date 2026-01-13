<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\EnterpriseFacilityDevice;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 基础-学生表
 */
class AccessLog extends Model
{

	protected $table = 'biz_access_log';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(EnterpriseFacilityDevice::class,'device_id','device_id')->with(['enterprise','facility','device']);
    }

    public function user(): HasOne
    {
        return $this->hasOne(AccessUser::class, 'user_id', 'user_id');
    }

}
