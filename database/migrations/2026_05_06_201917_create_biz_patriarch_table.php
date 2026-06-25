<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_patriarch';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-家长表');
            $table->id();
            $table->string('patriarch_name', 32)->nullable()->comment('家长姓名');
            $table->string('patriarch_nickname', 32)->nullable()->comment('家长昵称');
            $table->string('patriarch_sn', 32)->index()->nullable();
            $table->string('mobile', 16)->nullable()->comment('家长手机');
            $table->string('id_card', 32)->index()->nullable()->comment('身份证号');
            $table->tinyInteger('sex')->nullable()->comment('性别');
            $table->string('avatar')->nullable()->comment('头像');
            $table->tinyInteger('nation')->nullable()->default(1)->comment('民族');
            $table->tinyInteger('is_primary')->nullable()->default(1)->comment('监护人');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=1000000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 1000000000");
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
