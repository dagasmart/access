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
        Schema::connection('school')->create('biz_access_permission', function (Blueprint $table) {
            $table->comment('数智校园-门禁权限表');
            $table->increments('id');
            $table->smallInteger('permission_code')->nullable()->index('biz_access_permission_permission_code_idx')->comment('权限标识');
            $table->string('permission_name', 32)->nullable()->comment('权限名称');
            $table->json('permission_combo')->nullable()->comment('权限内容');
            $table->smallInteger('is_exclude')->nullable()->default(0)->index('biz_access_permission_is_exclude_idx')->comment('是否禁止');
            $table->json('exclude_date')->nullable()->comment('是否禁止');
            $table->smallInteger('is_allow')->nullable()->default(0)->index('biz_access_permission_is_allow_idx')->comment('是否允许');
            $table->json('allow_date')->nullable()->comment('允许日期');
            $table->integer('enterprise_id')->nullable()->index('biz_access_permission_enterprise_id_idx')->comment('机构单位');
            $table->json('body')->nullable();
            $table->string('module', 32)->nullable()->index('biz_access_permission_module_idx');
            $table->integer('mer_id')->nullable()->index('biz_access_permission_mer_id_idx');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            $table->index(['id'], 'biz_access_permission_id_idx');
            $table->unique(['permission_code', 'enterprise_id', 'module', 'mer_id'], 'biz_access_permission_permission_code_enterprise_id_module__key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_access_permission');
    }
};
