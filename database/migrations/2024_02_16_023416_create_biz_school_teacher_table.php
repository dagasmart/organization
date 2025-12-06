<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


return new class extends Migration
{
    protected $connection = 'bus';
    private string $table = 'biz_school_teacher';

    /**
     * 执行迁移
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            //创建表
            Schema::create($this->table, function (Blueprint $table) {
                $table->comment('数智校园-老师表');
                $table->id();

                $table->string('school_code',100)->comment('学校代码');
                $table->string('school_name',100)->comment('学校名称');
                $table->string('school_logo',255)->nullable()->comment('学校标志');
                $table->tinyInteger('school_nature')->nullable()->comment('学校性质');
                $table->tinyInteger('school_mode')->nullable()->comment('办学模式');
                $table->date('register_time')->nullable()->comment('注册日期');
                $table->integer('region')->nullable()->comment('所属地区');
                $table->string('school_address',100)->nullable()->comment('学校地址');
                $table->string('school_address_info',150)->nullable()->comment('详细地址');
                $table->string('location',100)->nullable()->comment('位置定位');
                $table->string('credit_code',64)->nullable()->comment('信用代码');
                $table->string('legal_person',64)->nullable()->comment('学校法人');
                $table->string('contacts_mobile',100)->nullable()->comment('联系电话');
                $table->string('contacts_email',64)->nullable()->comment('联系邮件');

                $table->string('module', 50)->nullable()->comment('模块');
                $table->bigInteger('mer_id')->nullable()->comment('商户id');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }






    /**
     * 迁移回滚
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            //检查是否存在数据
            $exists = DB::table($this->table)->exists();
            //不存在数据时，删除表
            if (!$exists) {
                //删除 reverse
                Schema::dropIfExists($this->table);
            }
        }
    }

};
