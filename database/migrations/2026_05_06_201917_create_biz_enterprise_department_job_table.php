<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_enterprise_department_job';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-部门-职务关联表');
            $table->id();
            $table->string('job_name', 32)->index()->comment('职务名称');
            $table->integer('parent_id')->nullable()->index()->comment('上级职务');
            $table->foreignId('department_id')->comment('部门id');
            $table->foreignId('enterprise_id')->comment('机构id');
            $table->tinyInteger('state')->comment('状态，1开启，0关闭');
            $table->tinyInteger('sort')->nullable()->comment('排序');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('enterprise_id');
            $table->index('department_id');
            $table->index('job_name');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'department_id', 'job_name']);

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->constrained('biz_enterprise_department')
                ->cascadeOnDelete();
        });
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
