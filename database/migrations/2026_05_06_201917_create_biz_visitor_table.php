<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_visitor';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-访客表');
            $table->id();
            $table->string('visitor_name', 32)->comment('访客姓名');
            $table->string('visitor_no', 32)->index()->nullable()->comment('访客编号');
            $table->string('mobile', 16)->index()->nullable()->comment('访客手机');
            $table->string('id_card', 32)->index()->nullable()->comment('身份证号');
            $table->tinyInteger('sex')->nullable()->comment('性别');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('remark')->nullable()->comment('备注');
            $table->tinyInteger('is_verify')->nullable()->default(1)->comment('核验状态:0否，1是');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=500000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 500000000");
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
