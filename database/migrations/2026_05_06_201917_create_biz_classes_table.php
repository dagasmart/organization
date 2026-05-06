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
        Schema::connection('school')->create('biz_classes', function (Blueprint $table) {
            $table->comment('数智校园-基础-班级表');
            $table->increments('id');
            $table->string('classes_code', 32)->nullable()->comment('班级代码');
            $table->string('classes_name', 32)->comment('班级名称');
            $table->integer('sort')->nullable()->comment('排序[0-255]');
            $table->smallInteger('status')->nullable()->default(1)->comment('状态');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();

            $table->index(['id'], 'biz_school_classroom_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_classes');
    }
};
