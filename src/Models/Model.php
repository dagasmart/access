<?php

namespace DagaSmart\Access\Models;

use DagaSmart\BizAdmin\Models\BaseModel;
use DagaSmart\BizAdmin\Scopes\ActiveScope;
use DagaSmart\Organization\Models\Enterprise;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *基座模型
 */
class Model extends BaseModel
{

    const string schema = 'school'; //连接的数据库名

    protected $connection = self::schema; // 使用school数据库连接

    public function __construct()
    {
        if(!isset($this->connection)){
            $this->setConnection($this->connection);
        }
        parent::__construct();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveScope(self::schema));
        parent::booted();
    }

    //关联机构
    public function base(): hasMany
    {
        return $this->hasMany(Enterprise::class, 'id', 'enterprise_id');
    }

}
