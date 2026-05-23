<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_facility';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-设施表');
            $table->id();
            $table->string('facility_name', 32)->nullable()->comment('设施名称');
            $table->string('facility_code', 32)->nullable()->comment('设施编码');
            $table->integer('parent_id')->nullable()->comment('上级id');
            $table->text('facility_combo')->nullable()->comment('设施配置');
            $table->tinyInteger('state')->nullable()->default(1)->comment('状态');
            $table->tinyInteger('sort')->nullable()->default(10)->comment('排序');
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
        if (Schema::hasTable($this->name)) {
            //检查是否存在数据
            $exists = DB::table($this->name)->exists();
            //不存在数据时，删除表
            if (!$exists) {
                //删除 reverse
                Schema::dropIfExists($this->name);
            }
        }
    }
};
