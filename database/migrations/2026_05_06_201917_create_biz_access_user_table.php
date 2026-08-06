<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_access_user';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-门禁用户表');
            $table->id();
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->string('user_name', 32)->nullable()->comment('用户姓名');
            $table->string('user_type', 16)->nullable()->comment('用户类型：worker员工,student学生,patriarch家长,visitor访客');
            $table->text('avatar')->nullable()->comment('用户照片');
            $table->string('id_card', 32)->nullable()->comment('身份证号');
            $table->string('mobile', 16)->nullable()->comment('手机号');
            $table->foreignId('enterprise_id')->nullable()->comment('机构单位id');
            $table->text('expiry_date')->nullable()->comment('使用期限：空为长期');
            $table->string('open_type', 64)->nullable()->comment('开锁模式：face人脸解锁，finger指纹解锁，card开锁卡片');
            $table->smallInteger('state')->nullable()->default(1)->comment('1正常，0停用');
            $table->smallInteger('sort')->nullable()->comment('排序[0-255]');
            $table->string('id_card_enc', 64)->nullable()->comment('身份证密文');
            $table->string('mobile_enc', 64)->nullable()->comment('手机号密文');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('user_id');
            $table->index('user_name');
            $table->index('user_type');
            $table->index('open_type');
            $table->index('expiry_date');
            $table->index('enterprise_id');
            $table->index('id_card');
            $table->index('state');
            $table->index('module');
            $table->index('mer_id');
            $table->index(['enterprise_id', 'user_type', 'user_id']);

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'user_type', 'user_id', 'module', 'mer_id'])->nullsNotDistinct();

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            // ✅ PostgreSQL HOT Update 优化（仍需原生 SQL）
            DB::connection($this->connection)->statement("ALTER TABLE $this->name SET (fillfactor = 90)");

            $driver = config('database.connections.'.$this->connection.'.driver');
            if ($driver == 'mysql') {
                DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=1000000000");
            }
            if ($driver == 'pgsql') {
                DB::statement("alter sequence {$this->name}_id_seq restart with 1000000000");
            }
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
