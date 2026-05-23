<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_access_user';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁用户表');
            $table->id();
            $table->integer('user_id')->nullable()->index()->comment('用户id');
            $table->string('user_name', 32)->nullable()->index()->comment('用户姓名');
            $table->text('user_avatar')->nullable()->comment('用户照片');
            $table->string('user_type', 16)->nullable()->index()->comment('用户类型：worker员工,student学生,patriarch家长,visitor访客');
            $table->string('id_card', 32)->nullable()->index()->comment('身份证号');
            $table->integer('enterprise_id')->nullable()->index()->comment('机构单位id');
            $table->text('expiry_date')->nullable()->index()->comment('使用期限：空为长期');
            $table->string('open_type', 64)->nullable()->comment('开锁模式：face人脸解锁，finger指纹解锁，card开锁卡片');
            $table->smallInteger('state')->nullable()->default(1)->index()->comment('1正常，0停用');
            $table->smallInteger('sort')->nullable()->comment('排序[0-255]');
            $table->string('id_card_enc', 64)->nullable()->comment('身份证密文');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->integer('mer_id')->nullable()->comment('商户');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

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
