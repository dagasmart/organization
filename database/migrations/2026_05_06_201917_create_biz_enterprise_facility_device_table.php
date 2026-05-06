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
        Schema::connection('school')->create('biz_enterprise_facility_device', function (Blueprint $table) {
            $table->comment('数智校园-机构-设施-设备关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('facility_id')->comment('设施id');
            $table->integer('device_id')->comment('设备id');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->integer('mer_id')->nullable()->comment('商户');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_facility_device');
    }
};
