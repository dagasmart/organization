<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_enterprise_department_job_worker';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-部门-职务-员工关联表');
            $table->foreignId('enterprise_id')->comment('机构id');
            $table->foreignId('department_id')->comment('部门id');
            $table->foreignId('job_id')->comment('职务id');
            $table->foreignId('worker_id')->comment('员工id');
            $table->string('worker_no', 32)->nullable()->comment('工号');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('enterprise_id');
            $table->index('department_id');
            $table->index('job_id');
            $table->index('worker_id');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'department_id', 'job_id', 'worker_id', 'module', 'mer_id']);

            // ✅ 4. 外键约束（复用已存在的单列索引，零额外开销）
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->constrained('biz_enterprise_department')
                ->cascadeOnDelete();

            $table->foreignId('job_id')
                ->constrained('biz_enterprise_department_job')
                ->cascadeOnDelete();

            $table->foreignId('worker_id')
                ->constrained('biz_worker')
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
