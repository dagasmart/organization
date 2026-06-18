<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_enterprise_department';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-部门关联表');
            $table->id();
            $table->string('department_name', 32)->comment('部门名称');
            $table->integer('parent_id')->nullable()->index()->comment('上级部门');
            $table->integer('enterprise_id')->index()->comment('机构id');
            $table->tinyInteger('state')->index()->comment('状态，1开启，0关闭');
            $table->tinyInteger('sort')->nullable()->comment('排序');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
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
