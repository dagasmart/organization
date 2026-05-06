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
        Schema::connection('school')->create('biz_enterprise_stage', function (Blueprint $table) {
            $table->comment('数智校园-机构-学段关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('stage_id')->comment('学段id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_stage');
    }
};
