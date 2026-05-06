<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('school')->create('biz_access_dispatch', function (Blueprint $table) {
            $table->comment('数智校园-门禁数据分发表');
            $table->bigIncrements('id');
            $table->integer('access_user_id')->nullable()->index('biz_access_dispatch_user_id_idx')->comment('门禁用户id');
            $table->integer('access_device_id')->nullable()->index('biz_access_dispatch_device_id_idx')->comment('门禁设备id');
            $table->integer('access_permission_id')->nullable()->index('biz_access_dispatch_permission_id_idx')->comment('门禁权限id');
            $table->integer('enterprise3_id')->nullable()->comment('门禁权限码');
            $table->string('auth_model', 16)->nullable()->default('days')->comment('授权类型:每天days、工作日workdays、自定义日期custom');
            $table->text('auth_date')->nullable()->comment('授权日期');
            $table->time('start_time')->nullable()->comment('有效期开始时间');
            $table->time('end_time')->nullable()->comment('有效期结束时间');
            $table->text('exclude_date')->nullable()->comment('排除日期');
            $table->smallInteger('sort')->nullable()->default(100)->comment('排序/优先级');
            $table->smallInteger('state')->nullable()->default(0)->comment('状态：0-待发，-1下发中，1成功，2失败');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();

            $table->index(['id'], 'biz_access_dispatch_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_access_dispatch');
    }
};
