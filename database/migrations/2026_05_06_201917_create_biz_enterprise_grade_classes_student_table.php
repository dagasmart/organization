<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $table = 'biz_enterprise_grade_classes_student';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->table)
        && Schema::create($this->table, function (Blueprint $table) {
            $table->comment('数智校园-机构-年级-班级-学生关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('grade_id')->comment('年级id');
            $table->integer('classes_id')->comment('班级id');
            $table->integer('student_id')->comment('学生id');
            $table->smallInteger('state')->nullable()->default(1)->comment('状态');
            $table->string('reason')->nullable()->comment('备注');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
        });

        // 创建触发器
        $trigger = $this->table . '_trigger';

        // 动态判断并安全删除
        DB::statement("
            DO \$\$
            BEGIN
                -- 检查该表上是否存在同名触发器
                IF EXISTS (
                    SELECT 1 FROM pg_trigger
                    WHERE tgname = '{$trigger}' AND tgrelid = '{$this->table}'::regclass
                ) THEN
                    EXECUTE 'DROP TRIGGER {$trigger} ON {$this->table}';
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            //检查是否存在数据
            $exists = DB::table($this->table)->exists();
            //不存在数据时，删除表
            if (!$exists) {
                //删除 reverse
                Schema::dropIfExists($this->table);
            }
        }
    }
};
