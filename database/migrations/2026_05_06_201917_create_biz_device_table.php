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
        Schema::connection('school')->create('biz_device', function (Blueprint $table) {
            $table->comment('数智校园-基础-设备表');
            $table->increments('id');
            $table->string('device_name', 32)->nullable()->comment('设备名称');
            $table->string('device_sn', 64)->nullable()->comment('设备编号');
            $table->string('device_model', 32)->nullable()->comment('设备型号');
            $table->string('device_brand', 16)->nullable()->comment('设备品牌');
            $table->string('device_type', 20)->nullable()->comment('设备类型');
            $table->string('device_pos', 16)->nullable()->comment('安装位置');
            $table->decimal('market_price', 10)->nullable()->default(0)->comment('零售价');
            $table->smallInteger('sort')->nullable()->default(10)->comment('排序');
            $table->smallInteger('state')->nullable()->default(1)->comment('状态');
            $table->text('device_desc')->nullable()->comment('设备描述');
            $table->smallInteger('online')->nullable()->default(0)->comment('设备状态');
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
        Schema::connection('school')->dropIfExists('biz_device');
    }
};
