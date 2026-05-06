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
        Schema::connection('school')->create('biz_patriarch', function (Blueprint $table) {
            $table->comment('数智校园-基础-家长表');
            $table->increments('id');
            $table->string('patriarch_name', 32)->nullable()->comment('家长姓名');
            $table->string('patriarch_nickname', 32)->nullable()->comment('家长昵称');
            $table->string('patriarch_sn', 32)->nullable();
            $table->string('mobile', 16)->nullable()->comment('家长手机');
            $table->string('id_card', 32)->nullable()->comment('身份证号');
            $table->smallInteger('sex')->nullable()->comment('性别');
            $table->string('avatar')->nullable()->comment('头像');
            $table->smallInteger('nation')->nullable()->default(1)->comment('民族');
            $table->smallInteger('is_primary')->nullable()->default(1)->comment('主关联');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_patriarch');
    }
};
