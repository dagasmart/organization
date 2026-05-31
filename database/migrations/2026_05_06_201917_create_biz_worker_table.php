<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_worker';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-员工表');
            $table->bigIncrements('id');
            $table->string('worker_no', 32)->nullable()->comment('员工编码');
            $table->string('worker_name', 32)->comment('员工姓名');
            $table->string('avatar', 200)->nullable()->comment('照片');
            $table->smallInteger('sex')->nullable()->comment('性别');
            $table->integer('place')->nullable()->comment('户籍地');
            $table->string('id_card', 32)->nullable()->comment('身份证号');
            $table->string('mobile', 32)->nullable()->comment('联系电话');
            $table->string('email', 64)->nullable()->comment('联系邮件');
            $table->integer('region_id')->nullable()->comment('户籍地');
            $table->jsonb('region_info')->nullable()->comment('地区信息');
            $table->string('address', 100)->nullable()->comment('家庭地址');
            $table->jsonb('family')->nullable()->comment('家庭成员');
            $table->smallInteger('nation')->nullable()->comment('民族');
            $table->string('party', 64)->nullable()->comment('政治面貌');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        $driver = config('database.connections.'. $this->connection . '.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=100000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 100000000");
        }
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
