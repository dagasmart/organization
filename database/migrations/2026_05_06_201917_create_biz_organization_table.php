<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_organization';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-机构源表');
            $table->id();
            $table->string('organization_code', 32)->comment('单位代码');
            $table->string('organization_name', 32)->comment('单位名称');
            $table->string('organization_logo')->nullable()->comment('单位标志');
            $table->tinyInteger('organization_nature')->nullable()->comment('单位性质');
            $table->tinyInteger('organization_mode')->nullable()->comment('单位模式');
            $table->string('organization_grade', 200)->nullable()->comment('学段年级');
            $table->date('register_time')->nullable()->comment('注册日期');
            $table->integer('region')->nullable()->comment('所属地区');
            $table->json('region_info')->nullable()->comment('地区信息');
            $table->string('organization_address', 100)->nullable()->comment('单位地址');
            $table->string('organization_address_info', 100)->nullable()->comment('详细地址');
            $table->string('location', 100)->nullable()->comment('位置定位');
            $table->string('social_credit_code', 64)->nullable()->comment('社会信用代码');
            $table->string('legal_person', 64)->nullable()->comment('单位法人');
            $table->string('contacts_mobile', 100)->nullable()->comment('联系电话');
            $table->string('contacts_email', 64)->nullable()->comment('联系邮件');
            $table->tinyInteger('state')->nullable()->default(1)->comment('状态,1开启，0禁用');
            $table->unsignedBigInteger('creator_id')->nullable()->comment('主创商户id');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->unsignedBigInteger('mer_id')->nullable()->comment('商户');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
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
