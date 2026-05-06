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
        Schema::connection('school')->create('biz_job', function (Blueprint $table) {
            $table->comment('数智校园-基础-职务表');
            $table->smallIncrements('id');
            $table->string('job_name', 32)->nullable()->comment('职务名称');
            $table->string('tag', 100)->nullable()->comment('职务职责');
            $table->smallInteger('parent_id')->nullable()->comment('父id');
            $table->smallInteger('sort')->nullable()->comment('排序[0-255]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_job');
    }
};
