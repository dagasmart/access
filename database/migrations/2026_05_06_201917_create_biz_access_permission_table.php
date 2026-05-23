<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_access_permission';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁权限表');
            $table->id();
            $table->tinyInteger('permission_code')->nullable()->index()->comment('权限标识');
            $table->string('permission_name', 32)->nullable()->comment('权限名称');
            $table->json('permission_combo')->nullable()->comment('权限内容');
            $table->tinyInteger('is_exclude')->nullable()->default(0)->index()->comment('是否禁止');
            $table->json('exclude_date')->nullable()->comment('是否禁止');
            $table->tinyInteger('is_allow')->nullable()->default(0)->index()->comment('是否允许');
            $table->json('allow_date')->nullable()->comment('允许日期');
            $table->integer('enterprise_id')->nullable()->index()->comment('机构单位');
            $table->json('body')->nullable();
            $table->string('module', 32)->nullable()->index();
            $table->integer('mer_id')->nullable()->index();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            $table->index(['id']);
            $table->unique(['permission_code', 'enterprise_id', 'module', 'mer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->name)) {
            //检查是否存在数据
            $exists = DB::table($this->name)->exists();
            //不存在数据时，删除表
            if (!$exists) {
                //删除 reverse
                Schema::dropIfExists($this->name);
            }
        }
    }
};
