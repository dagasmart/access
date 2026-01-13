<?php

namespace DagaSmart\Access\Models;

/**
 * 基础-门禁用户表
 */
class AccessUser extends Model
{

	protected $table = 'biz_access_user';
    protected $primaryKey = 'id';

    public $timestamps = true;


}
