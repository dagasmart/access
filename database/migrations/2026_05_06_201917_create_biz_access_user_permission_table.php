<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_access_user_permission';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁用户权限表');
            $table->id();
            $table->integer('access_user_id')->nullable()->index()->comment('门禁用户id');
            $table->integer('access_device_id')->nullable()->index()->comment('门禁设备id');
            $table->integer('access_permission_id')->nullable()->comment('门禁权限id');
            $table->integer('access_permission_code')->nullable()->comment('门禁权限码');
            $table->string('auth_model', 16)->nullable()->default('days')->comment('授权类型:每天days、工作日workdays、自定义日期custom');
            $table->text('auth_date')->nullable()->comment('授权日期');
            $table->time('start_time')->nullable()->comment('权限有效期开始时间');
            $table->time('end_time')->nullable()->comment('权限有效期结束时间');
            $table->text('exclude_date')->nullable()->comment('排除日期');
            $table->tinyInteger('state')->nullable()->default(1)->comment('权限状态：1-有效 0-无效（撤销权限）');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();

            $table->index(['id']);
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
