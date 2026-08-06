<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_enterprise_department_visitor';

    public function up(): void
    {
        ! Schema::connection($this->connection)->hasTable($this->name)
        && Schema::connection($this->connection)->create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-部门-访客关联表');

            // 1. 列定义
            $table->foreignId('enterprise_id')->comment('机构id');
            $table->foreignId('department_id')->comment('部门id');
            $table->foreignId('visitor_id')->comment('访客id');
            $table->char('visitor_no', 32)->nullable()->comment('家长编号');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');

            // 2. 单列索引（仅保留联合唯一索引最左前缀未覆盖的列）
            $table->index('department_id');
            $table->index('visitor_id');
            $table->index('module');
            $table->index('mer_id');

            // 3. 唯一约束（最左前缀 enterprise_id 已覆盖查询）
            $table->unique(
                ['enterprise_id', 'department_id', 'visitor_id', 'module', 'mer_id']
            )->nullsNotDistinct();

            // 4. 外键约束（使用 foreign() 避免重复创建列）
            $table->foreign('enterprise_id')
                ->references('id')
                ->on('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('biz_enterprise_department')
                ->cascadeOnDelete();

            $table->foreign('visitor_id')
                ->references('id')
                ->on('biz_visitor')
                ->cascadeOnDelete();
        });

        // 5. 填充因子优化
        DB::connection($this->connection)->statement(
            "ALTER TABLE {$this->name} SET (fillfactor = 90);"
        );
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable($this->name)) {
            $exists = DB::connection($this->connection)->table($this->name)->exists();
            if ($exists) {
                throw new RuntimeException(
                    "无法回滚 migration：表 {$this->name} 中仍存在数据，请先清理数据后再回滚。"
                );
            }
            Schema::connection($this->connection)->dropIfExists($this->name);
        }
    }
};
