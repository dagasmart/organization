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
        Schema::connection('school')->create('biz_grade', function (Blueprint $table) {
            $table->comment('数智校园-基础-年级表');
            $table->increments('id');
            $table->integer('grade_no')->nullable()->index('biz_grade_grade_no_idx')->comment('年级编号');
            $table->string('grade_name', 20)->nullable()->index('biz_grade_grade_name_idx')->comment('年级名称');
            $table->integer('parent_id')->nullable()->default(0)->index('biz_grade_parent_id_idx')->comment('父级id');
            $table->smallInteger('sort')->nullable()->default(0)->comment('排序[0-255]');

            $table->unique(['grade_no', 'parent_id'], 'biz_grade_grade_no_parent_id_key');
            $table->index(['id'], 'biz_grade_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_grade');
    }
};
