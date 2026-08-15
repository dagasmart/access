<?php

namespace DagaSmart\Access\Models;

use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-设备表
 */
class AccessDispatch extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;
    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];

    protected $table = 'biz_access_dispatch';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public function user(): HasOne
    {
        return $this->hasOne(AccessUser::class, 'id', 'access_user_id');
    }

    public function device(): HasOne
    {
        return $this->hasOne(AccessDevice::class, 'id', 'access_device_id')->with('rel');
    }

    public function permission(): HasOne
    {
        return $this->hasOne(AccessPermission::class, 'id', 'access_permission_id');
    }
}
