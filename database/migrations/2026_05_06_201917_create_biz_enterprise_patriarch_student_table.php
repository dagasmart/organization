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
        Schema::connection('school')->create('biz_enterprise_patriarch_student', function (Blueprint $table) {
            $table->comment('数智校园-机构-家长-学生关联表');
            $table->integer('enterprise_id')->nullable()->comment('机构id');
            $table->integer('patriarch_id')->nullable()->comment('家长id');
            $table->integer('student_id')->nullable()->comment('学生id');
            $table->smallInteger('relationship_id')->nullable()->comment('关系id');
            $table->string('patriarch_sn', 32)->nullable()->comment('家长编号');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->integer('mer_id')->nullable()->comment('商户');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_patriarch_student');
    }
};
