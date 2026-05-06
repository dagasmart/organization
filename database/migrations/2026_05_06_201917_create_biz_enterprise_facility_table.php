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
        Schema::connection('school')->create('biz_enterprise_facility', function (Blueprint $table) {
            $table->comment('数智校园-机构-设施关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('facility_id')->comment('设施id');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_facility');
    }
};
