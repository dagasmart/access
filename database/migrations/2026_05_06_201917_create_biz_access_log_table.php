<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_access_log';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁记录表');
            $table->id();
            $table->unsignedBigInteger('enterprise_id')->nullable()->index()->comment('机构id');
            $table->unsignedBigInteger('facility_id')->nullable()->index()->comment('设施id');
            $table->unsignedBigInteger('device_id')->nullable()->index()->comment('设备id');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('用户id');
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
