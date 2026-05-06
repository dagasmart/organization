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
        Schema::connection('school')->create('biz_stream', function (Blueprint $table) {
            $table->comment('数智校园-基础-推拉流表');
            $table->increments('id')->comment('ID');
            $table->string('title', 32)->nullable()->comment('名称');
            $table->string('agree', 32)->nullable()->comment('协议（源）');
            $table->string('remote')->nullable()->comment('输入流（源）');
            $table->string('name', 32)->nullable()->comment('输出流名称');
            $table->string('url')->nullable()->comment('输出流地址');
            $table->string('fix', 32)->nullable()->comment('输出流格式');
            $table->string('sip', 32)->nullable()->default('127.0.0.1')->comment('分流内网IP');
            $table->smallInteger('port')->nullable()->default(8000)->comment('分流公网端口');
            $table->string('piont', 32)->nullable()->comment('经纬度');
            $table->smallInteger('sort')->nullable()->default(10)->comment('排序[0-255]');
            $table->smallInteger('state')->nullable()->default(0)->comment('状态：0禁用，1启用');
            $table->smallInteger('hot')->nullable()->default(0)->comment('热点：1是， 0否');
            $table->smallInteger('top')->nullable()->default(0)->comment('置顶：1是，0否');
            $table->smallInteger('online')->nullable()->default(0)->comment('1在线，0离线');
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
        Schema::connection('school')->dropIfExists('biz_stream');
    }
};
