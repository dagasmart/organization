<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $table = 'biz_department';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->table)
        && Schema::create($this->table, function (Blueprint $table) {
            $table->comment('数智校园-基础-部门表');
            $table->id();
            $table->string('department_name', 64)->nullable()->comment('部门名称');
            $table->integer('parent_id')->nullable()->comment('上级id');
            $table->tinyInteger('sort')->nullable()->comment('排序');
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=10000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 10000000");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            // 检查是否存在数据
            $exists = DB::table($this->table)->exists();
            // 不存在数据时，删除表
            if (! $exists) {
                // 删除 reverse
                Schema::dropIfExists($this->table);
            }
        }
    }
};
