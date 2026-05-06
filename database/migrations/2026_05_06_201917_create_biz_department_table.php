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
        Schema::connection('school')->create('biz_department', function (Blueprint $table) {
            $table->comment('数智校园-基础-部门表');
            $table->increments('id');
            $table->string('department_name', 64)->nullable()->comment('部门名称');
            $table->integer('parent_id')->nullable()->comment('上级id');
            $table->smallInteger('sort')->nullable()->comment('排序');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_department');
    }
};
