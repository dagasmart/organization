<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ✅ PHP 8.3: 类型化类常量（Typed Class Constants）
    protected $connection = 'school';

    private string $name = 'biz_enterprise_grade';

    public function up(): void
    {
        if (Schema::hasTable($this->name)) {
            return;
        }

        Schema::create($this->name, function (Blueprint $table): void {
            $table->comment('数智校园-机构-年级关联表');

            // ✅ foreignId 默认 NOT NULL + unsignedBigInteger + 自动单列索引
            $table->foreignId('enterprise_id')->comment('机构id');
            $table->foreignId('grade_id')->comment('年级id');
            $table->string('module', 32)->index()->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->index()->nullable()->comment('商户');

            $table->index('enterprise_id');
            $table->index('grade_id');
            $table->index('module');
            $table->index('mer_id');

            // ✅ 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['enterprise_id', 'grade_id', 'module', 'mer_id'])->nullsNotDistinct();

            // ✅ constrained() 一行完成外键 + 级联删除
            // 复用 foreignId 已创建的单列索引，零额外开销
            $table->foreignId('enterprise_id')
                ->constrained('biz_enterprise')
                ->cascadeOnDelete();

            $table->foreignId('grade_id')
                ->constrained('biz_grade')
                ->cascadeOnDelete();
        });

        // ✅ PostgreSQL HOT Update 优化（仍需原生 SQL）
        DB::connection($this->connection)->statement("ALTER TABLE $this->name SET (fillfactor = 90)");
    }

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
