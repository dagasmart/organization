<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_nature';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-性质表');
            $table->id();
            $table->string('label', 32)->nullable()->comment('键名');
            $table->string('value', 32)->nullable()->comment('键值');
            $table->string('type', 32)->nullable()->comment('类别');
            $table->tinyInteger('state')->default(1)->comment('状态');
            $table->tinyInteger('sort')->default(10)->comment('排序');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        // 在创建表之后，紧接着插入基础数据
        DB::table($this->name)->insertOrIgnoreReturning([
            ['id' => 1, 'label' => '政府机关', 'value' => 1, 'type' => 'agency', 'sort' => 1],
            ['id' => 2, 'label' => '公益事业', 'value' => 2, 'type' => 'common', 'sort' => 2],
            ['id' => 3, 'label' => '公办学校', 'value' => 3, 'type' => 'school', 'sort' => 3],
            ['id' => 4, 'label' => '民办学校', 'value' => 4, 'type' => 'school', 'sort' => 4],
            ['id' => 5, 'label' => '独立学院', 'value' => 5, 'type' => 'school', 'sort' => 5],
            ['id' => 6, 'label' => '中外办学', 'value' => 6, 'type' => 'school', 'sort' => 6],
            ['id' => 7, 'label' => '私立学校', 'value' => 7, 'type' => 'school', 'sort' => 7],
            ['id' => 8, 'label' => '国有企业', 'value' => 8, 'type' => 'company', 'sort' => 8],
            ['id' => 9, 'label' => '集体企业', 'value' => 9, 'type' => 'company', 'sort' => 9],
            ['id' => 10, 'label' => '民营企业', 'value' => 10, 'type' => 'company', 'sort' => 10],
            ['id' => 11, 'label' => '外资企业', 'value' => 11, 'type' => 'company', 'sort' => 11],
            ['id' => 12, 'label' => '合资企业', 'value' => 12, 'type' => 'company', 'sort' => 12],
            ['id' => 13, 'label' => '股份公司', 'value' => 13, 'type' => 'company', 'sort' => 13],
            ['id' => 14, 'label' => '责任公司', 'value' => 14, 'type' => 'company', 'sort' => 14],
            ['id' => 15, 'label' => '个体工商', 'value' => 15, 'type' => 'company', 'sort' => 15],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->name)) {
            // 检查是否存在数据
            $exists = DB::table($this->name)->exists();
            // 不存在数据时，删除表
            if (! $exists) {
                // 删除 reverse
                Schema::dropIfExists($this->name);
            }
        }
    }
};
