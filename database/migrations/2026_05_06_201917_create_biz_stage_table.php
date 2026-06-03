<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_stage';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-学段表');
            $table->id();
            $table->string('stage_no', 100)->nullable()->comment('学段号');
            $table->string('stage_name', 50)->nullable()->comment('学段名');
            $table->string('type', 32)->nullable()->comment('类别');
            $table->tinyInteger('sort')->nullable()->comment('排序');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        // 在创建表之后，紧接着插入基础数据
        DB::table($this->name)->insertOrIgnoreReturning([
            ['id' => 1, 'stage_no' => '1000', 'stage_name' => '幼儿园', 'type' => 'school', 'sort' => '1'],
            ['id' => 2, 'stage_no' => '1010', 'stage_name' => '小学', 'type' => 'school', 'sort' => '2'],
            ['id' => 3, 'stage_no' => '1020', 'stage_name' => '初级中学', 'type' => 'school', 'sort' => '3'],
            ['id' => 4, 'stage_no' => '1030', 'stage_name' => '高级中学', 'type' => 'school', 'sort' => '4'],
            ['id' => 5, 'stage_no' => '1010,1020', 'stage_name' => '九年一贯制', 'type' => 'school', 'sort' => '5'],
            ['id' => 6, 'stage_no' => '1010,1020,1030', 'stage_name' => '十二年一贯制', 'type' => 'school', 'sort' => '6'],
            ['id' => 7, 'stage_no' => '1070', 'stage_name' => '中专', 'type' => 'school', 'sort' => '7'],
            ['id' => 8, 'stage_no' => '1080', 'stage_name' => '大学', 'type' => 'school', 'sort' => '8'],
            ['id' => 9, 'stage_no' => '1110', 'stage_name' => '事业编制', 'type' => 'common', 'sort' => '1'],
            ['id' => 10, 'stage_no' => '1130', 'stage_name' => '股份责任', 'type' => 'company', 'sort' => '1'],
            ['id' => 11, 'stage_no' => '1150', 'stage_name' => '独立资金', 'type' => 'company', 'sort' => '3'],
            ['id' => 12, 'stage_no' => '1160', 'stage_name' => '募集资金', 'type' => 'company', 'sort' => '4'],
            ['id' => 13, 'stage_no' => '1100', 'stage_name' => '行政服务', 'type' => 'agency', 'sort' => '1'],
            ['id' => 14, 'stage_no' => '1120', 'stage_name' => '社会团体', 'type' => 'common', 'sort' => '2'],
            ['id' => 15, 'stage_no' => '1140', 'stage_name' => '合资办企', 'type' => 'company', 'sort' => '2'],
            ['id' => 16, 'stage_no' => '1170', 'stage_name' => '自筹资金', 'type' => 'company', 'sort' => '5'],
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
