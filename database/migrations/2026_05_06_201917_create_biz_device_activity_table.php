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
        Schema::connection('school')->create('biz_device_activity', function (Blueprint $table) {
            $table->comment('数智校园-基础-设备活检表');
            $table->increments('id');
            $table->integer('device_id')->nullable()->index('biz_device_activity_device_id_idx')->comment('设备id');
            $table->string('device_sn', 32)->index('biz_device_activity_device_sn_idx')->comment('设备编码');
            $table->string('device_type', 20)->nullable()->index('biz_device_activity_device_type_idx')->comment('设备类型');
            $table->string('device_mac', 32)->nullable()->comment('MAC');
            $table->timestamp('device_time')->nullable()->index('biz_device_activity_device_time_idx')->comment('响应时间');
            $table->json('device_res')->nullable()->comment('响应结果');
            $table->string('device_version', 32)->nullable()->comment('版本号');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            $table->unique(['device_sn', 'device_type'], 'biz_device_activity_device_id_device_sn_device_type_key');
            $table->index(['device_sn', 'device_type'], 'biz_device_activity_device_sn_device_type_idx');
            $table->index(['id'], 'biz_device_activity_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_device_activity');
    }
};
