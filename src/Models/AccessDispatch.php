<?php

namespace DagaSmart\Access\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-设备表
 */
class AccessDispatch extends Model
{

    protected $table = 'biz_access_dispatch';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function user(): hasOne
    {
        return $this->hasOne(AccessUser::class,'id','access_user_id');
    }

    public function device(): hasOne
    {
        return $this->hasOne(AccessDevice::class,'id','access_device_id')->with('rel');
    }

}
