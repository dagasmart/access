<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Organization\Models\Enterprise;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-进出记录表
 */
class AccessPermission extends Model
{

	protected $table = 'biz_access_permission';
    protected $primaryKey = 'id';

    public $timestamps = true;

    public function rel(): hasOne
    {
        return $this->hasOne(Enterprise::class,'id','enterprise_id')->select(['id', 'enterprise_name']);
    }


}
