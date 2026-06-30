<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_enterprise_grade_classes_student';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-年级-班级-学生关联表');
            $table->foreignId('enterprise_id')->comment('机构id');
            $table->foreignId('grade_id')->comment('年级id');
            $table->foreignId('classes_id')->comment('班级id');
            $table->foreignId('student_id')->comment('学生id');
            $table->char('student_no', 32)->nullable()->comment('学号');
            $table->smallInteger('state')->default(1)->comment('状态：1-正常 2-毕业 3-转学 4-休学 5-退学');
            $table->string('reason')->nullable()->comment('备注');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('enterprise_id');
            $table->index('grade_id');
            $table->index('classes_id');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'grade_id', 'classes_id', 'student_id', 'module', 'mer_id']);

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreignId('grade_id')
                ->constrained('biz_grade')
                ->cascadeOnDelete();

            $table->foreignId('classes_id')
                ->constrained('biz_classes')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('biz_student')
                ->cascadeOnDelete();

        });

        // 创建触发器
        $trigger = $this->name.'_trigger';

        // 动态判断并安全删除
        DB::statement("
            DO \$\$
            BEGIN
                -- 检查该表上是否存在同名触发器
                IF EXISTS (
                    SELECT 1 FROM pg_trigger
                    WHERE tgname = '{$trigger}' AND tgrelid = '{$this->name}'::regclass
                ) THEN
                    EXECUTE 'DROP TRIGGER {$trigger} ON {$this->name}';
                END IF;
            END \$\$;
        ");

        // ✅ PostgreSQL HOT Update 优化（仍需原生 SQL）
        DB::connection($this->connection)->statement("ALTER TABLE $this->name SET (fillfactor = 90)");
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
