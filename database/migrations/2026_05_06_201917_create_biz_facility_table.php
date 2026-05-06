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
        Schema::connection('school')->create('biz_facility', function (Blueprint $table) {
            $table->comment('数智校园-基础-设施表');
            $table->increments('id');
            $table->string('facility_name', 32)->nullable()->comment('设施名称');
            $table->string('facility_code', 32)->nullable()->comment('设施编码');
            $table->integer('parent_id')->nullable()->comment('上级id');
            $table->text('facility_combo')->nullable()->comment('设施配置');
            $table->smallInteger('state')->nullable()->default(1)->comment('状态');
            $table->smallInteger('sort')->nullable()->default(10)->comment('排序');
            $table->string('facility_desc')->nullable()->comment('设施描述');
            $table->string('facility_tag', 100)->nullable()->comment('场景标签');
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
        Schema::connection('school')->dropIfExists('biz_facility');
    }
};
