<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('school')->create('biz_student', function (Blueprint $table) {
            $table->comment('数智校园-基础-学生表');
            $table->bigIncrements('id');
            $table->string('student_no', 64)->nullable()->comment('学号');
            $table->string('student_name', 64)->comment('姓名');
            $table->string('avatar')->nullable()->comment('照片');
            $table->smallInteger('sex')->nullable()->comment('性别');
            $table->smallInteger('nation')->nullable()->default(1)->comment('民族');
            $table->integer('place')->nullable()->comment('户籍地');
            $table->string('id_card', 32)->nullable()->comment('身份证号');
            $table->string('mobile', 32)->nullable()->comment('联系电话');
            $table->string('email', 64)->nullable()->comment('联系邮件');
            $table->integer('region_id')->nullable()->comment('户籍地');
            $table->jsonb('region_info')->nullable()->comment('地区信息');
            $table->string('address')->nullable()->comment('家庭地址');
            $table->jsonb('family')->nullable()->comment('家庭成员');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_student');
    }
};
