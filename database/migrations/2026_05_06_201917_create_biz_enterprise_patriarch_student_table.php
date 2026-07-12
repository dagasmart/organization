<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_enterprise_patriarch_student';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-家长-学生关联表');
            // ✅ 1. 字段精简与类型对齐（减少磁盘IO与内存占用）
            $table->unsignedBigInteger('enterprise_id')->comment('机构id');
            $table->unsignedBigInteger('patriarch_id')->comment('家长id');
            $table->unsignedBigInteger('student_id')->comment('学生id');
            $table->unsignedTinyInteger('relationship_id')->default(1)->comment('关系id');
            $table->char('patriarch_no', 32)->nullable()->comment('家长编号'); // char比varchar省1字节开销
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('enterprise_id');
            $table->index('patriarch_id');
            $table->index('student_id');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'patriarch_id', 'student_id', 'module', 'mer_id']);

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreignId('patriarch_id')
                ->constrained('biz_patriarch')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('biz_student')
                ->cascadeOnDelete();
        });

        // ✅ 5. 极致优化：设置填充因子（HOT Update 神器）
        // 关联表极少UPDATE，但若有软删除或状态变更，90%填充因子可预留页内空间
        // 避免行更新时触发页分裂，大幅提升写入与级联删除性能
        DB::connection($this->connection)->statement("ALTER TABLE $this->name SET (fillfactor = 90);");
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
