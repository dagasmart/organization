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
        Schema::connection('school')->create('biz_enterprise_grade_classes_student', function (Blueprint $table) {
            $table->comment('数智校园-机构-年级-班级-学生关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('grade_id')->comment('年级id');
            $table->integer('classes_id')->comment('班级id');
            $table->integer('student_id')->comment('学生id');
            $table->smallInteger('state')->nullable()->default(1)->comment('状态');
            $table->string('reason')->nullable()->comment('备注');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
        });

        // 创建触发器
        DB::connection('school')->statement("CREATE TRIGGER biz_enterprise_grade_classes_student_trigger AFTER INSERT ON biz_enterprise_grade_classes_student FOR EACH ROW BEGIN ; END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_grade_classes_student');
    }
};
