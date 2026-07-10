<?php

namespace DagaSmart\Access\Models;

use DagaSmart\Access\Enums\Enum;
use DagaSmart\BizAdmin\Traits\ModuleMerIdTrait;
use DagaSmart\Organization\Models\Enterprise;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 门禁-权限表
 */
class AccessPermission extends Model
{
    // 一行代码，自动拥有读隔离和写自动填充能力
    use ModuleMerIdTrait;

    protected $table = 'biz_access_permission';

    protected $primaryKey = 'id';

    // 按需开启,模型表没有标记为空数组
    protected $activeScopeFields = ['module', 'mer_id'];
    protected $hidden = ['module', 'mer_id'];

    public $timestamps = true;

    protected $casts = [
        'permission_combo' => 'array',
        'exclude_date' => 'array',
        'allow_date' => 'array',
        'body' => 'array',
        'combo' => 'string',
    ];

    protected $appends = ['combo'];

    public function getComboAttribute(): ?string
    {
        $row = [];
        $combo = $this->permission_combo;
        if ($combo) {
            array_walk($combo, function ($item) use (&$row) {
                $key = str_replace('week', null, key($item));
                $row[] = $this->arrayWeeks($key);
                // $row[] = $key;
            });
        }

        return $row ? implode(',', $row) : null;
    }

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

    public function rel(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'id', 'enterprise_id')->select(['id', 'enterprise_name']);
    }

    /**
     * 星期 大小写转换
     */
    public function arrayWeeks($int = null, $type = 2)
    {
        $weeks = Enum::weeks($type);
        if ($int !== null) {
            $column = array_column($weeks, 'label', 'value');

            return $column[$int];
        }

        return $weeks;
    }
}
