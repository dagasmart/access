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
        Schema::connection('school')->create('biz_access_log', function (Blueprint $table) {
            $table->comment('数智校园-门禁记录表');
            $table->bigIncrements('id');
            $table->integer('enterprise_id')->nullable()->comment('机构id');
            $table->integer('facility_id')->nullable()->comment('设施id');
            $table->integer('device_id')->nullable()->comment('设备id');
            $table->string('user_id', 64)->nullable()->comment('用户id');
            $table->string('user_name', 32)->nullable()->comment('用户姓名');
            $table->string('device_pos', 32)->nullable()->comment('设备位置');
            $table->text('scene_photo')->nullable()->comment('现场照片');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_access_log');
    }
};
