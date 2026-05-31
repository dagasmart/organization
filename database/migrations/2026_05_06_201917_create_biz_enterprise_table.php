<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_enterprise';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-机构表');
            $table->id();
            $table->string('enterprise_code')->comment('单位代码');
            $table->string('enterprise_name')->comment('单位名称');
            $table->string('enterprise_logo')->nullable()->comment('单位标志');
            $table->tinyInteger('enterprise_nature')->nullable()->comment('单位性质');
            $table->tinyInteger('enterprise_mode')->nullable()->comment('单位模式');
            $table->string('enterprise_grade')->nullable()->comment('学段年级');
            $table->date('register_time')->nullable()->comment('注册日期');
            $table->integer('region')->nullable()->comment('所属地区');
            $table->json('region_info')->nullable()->comment('地区信息');
            $table->string('enterprise_address')->nullable()->comment('单位地址');
            $table->string('enterprise_address_info')->nullable()->comment('详细地址');
            $table->string('location', 100)->nullable()->comment('位置定位');
            $table->string('credit_code', 64)->nullable()->comment('信用代码');
            $table->string('legal_person', 64)->nullable()->comment('单位法人');
            $table->string('contacts_mobile', 100)->nullable()->comment('联系电话');
            $table->string('contacts_email', 64)->nullable()->comment('联系邮件');
            $table->tinyInteger('state')->nullable()->default(1)->comment('状态,1开启，0禁用');
            $table->string('module', 50)->nullable()->comment('模块');
            $table->integer('mer_id')->nullable()->comment('商户id');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        $driver = config('database.connections.'. $this->connection . '.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=9000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 9000000");
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
