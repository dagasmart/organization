<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_job';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-职务表');
            $table->id();
            $table->string('job_name', 32)->nullable()->comment('职务名称');
            $table->string('tag', 100)->nullable()->comment('职务职责');
            $table->smallInteger('parent_id')->nullable()->comment('父id');
            $table->smallInteger('sort')->nullable()->comment('排序[0-255]');
        });

        // 在创建表之后，紧接着插入基础数据
        DB::table($this->name)->insert([
            ['label' => '政府机关', 'value' => 1, 'type' => 'agency', 'sort' => 1],
            ['label' => '公益事业', 'value' => 2, 'type' => 'common', 'sort' => 2],
            ['label' => '公办学校', 'value' => 3, 'type' => 'school', 'sort' => 3],
            ['label' => '民办学校', 'value' => 4, 'type' => 'school', 'sort' => 4],
            ['label' => '独立学院', 'value' => 5, 'type' => 'school', 'sort' => 5],
            ['label' => '中外办学', 'value' => 6, 'type' => 'school', 'sort' => 6],
            ['label' => '私立学校', 'value' => 7, 'type' => 'school', 'sort' => 7],
            ['label' => '国有企业', 'value' => 8, 'type' => 'company', 'sort' => 8],
            ['label' => '集体企业', 'value' => 9, 'type' => 'company', 'sort' => 9],
            ['label' => '民营企业', 'value' => 10, 'type' => 'company', 'sort' => 10],
            ['label' => '外资企业', 'value' => 11, 'type' => 'company', 'sort' => 11],
            ['label' => '合资企业', 'value' => 12, 'type' => 'company', 'sort' => 12],
            ['label' => '股份公司', 'value' => 13, 'type' => 'company', 'sort' => 13],
            ['label' => '责任公司', 'value' => 14, 'type' => 'company', 'sort' => 14],
            ['label' => '个体工商', 'value' => 15, 'type' => 'company', 'sort' => 15],
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
