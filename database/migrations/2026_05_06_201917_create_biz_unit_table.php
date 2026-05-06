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
        Schema::connection('school')->create('biz_unit', function (Blueprint $table) {
            $table->comment('数智校园-基础-单位换算表');
            $table->increments('id');
            $table->string('unit_name', 16)->nullable()->comment('单位名称');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_unit');
    }
};
