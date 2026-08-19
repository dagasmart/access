<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_access_dispatch';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁数据分发表');
            $table->id();
            $table->foreignId('access_user_id')->nullable()->index()->comment('门禁用户id');
            $table->foreignId('access_device_id')->nullable()->index()->comment('门禁设备id');
            $table->foreignId('access_permission_id')->nullable()->index()->comment('门禁权限id');
            $table->integer('enterprise_id')->nullable()->comment('门禁权限码');
            $table->string('auth_model', 24)->nullable()->default('days')->comment('授权类型:每天days、工作日workdays、自定义日期custom');
            $table->text('auth_date')->nullable()->comment('授权日期');
            $table->time('start_time')->nullable()->comment('有效期开始时间');
            $table->time('end_time')->nullable()->comment('有效期结束时间');
            $table->text('exclude_date')->nullable()->comment('排除日期');
            $table->smallInteger('sort')->nullable()->default(100)->comment('排序/优先级');
            $table->string('user_type', 24)->nullable()->comment('用户类型：worker员工,student学生,patriarch家长,visitor访客');
            $table->smallInteger('state')->nullable()->default(0)->comment('状态：0-待发，-1下发中，1成功，2失败');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index(['access_user_id', 'access_device_id', 'access_permission_id']);
            $table->index('access_user_id');
            $table->index('access_device_id');
            $table->index('access_permission_id');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['access_user_id', 'access_device_id', 'access_permission_id', 'module', 'mer_id'])->nullsNotDistinct();

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('access_user_id')
                ->constrained('biz_access_user')
                ->cascadeOnDelete();

            $table->foreignId('access_permission_id')
                ->constrained('biz_access_permission')
                ->cascadeOnDelete();

            // ✅ PostgreSQL HOT Update 优化（仍需原生 SQL）
            DB::connection($this->connection)->statement("ALTER TABLE $this->name SET (fillfactor = 90)");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->name)) {
            // 检查是否存在数据
            $exists = DB::table($this->name)->exists();
            // 不存在数据时，删除表
            if (! $exists) {
                // 删除 reverse
                Schema::dropIfExists($this->name);
            }
        }
    }
};
