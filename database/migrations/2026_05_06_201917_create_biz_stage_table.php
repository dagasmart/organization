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
        Schema::connection('school')->create('biz_stage', function (Blueprint $table) {
            $table->comment('数智校园-基础-学段表');
            $table->increments('id');
            $table->string('stage_no', 100)->nullable()->comment('学段号');
            $table->string('stage_name', 50)->nullable()->comment('学段名');
            $table->string('type', 32)->nullable()->comment('类别');
            $table->smallInteger('sort')->nullable()->comment('排序');
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
        Schema::connection('school')->dropIfExists('biz_stage');
    }
};
