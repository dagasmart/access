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

    protected $casts = [
        'permission_combo' => 'array',
        'exclude_date' => 'array',
        'allow_date' => 'array',
        'body' => 'array',
    ];


//    public function setAllowDateAttribute($value): void
//    {
//        $this->attributes['allow_date'] = $value && is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
//    }
//
//    public function getExcludeDateAttribute($value)
//    {
//        return $value && isJsonString($value) ? json_decode($value, true) : null;
//    }
//
//    public function setExcludeDateAttribute($value): void
//    {
//        $this->attributes['exclude_date'] = $value && is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
//    }

//    public function setBodyAttribute($value): void
//    {
//        $this->attributes['body'] = json_encode($value, JSON_UNESCAPED_UNICODE);
//    }

    public function rel(): hasOne
    {
        return $this->hasOne(Enterprise::class,'id','enterprise_id')->select(['id', 'enterprise_name']);
    }


}
