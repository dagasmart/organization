<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_grade';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-年级表');
            $table->id();
            $table->integer('grade_no')->nullable()->index()->comment('年级编号');
            $table->string('grade_name', 20)->nullable()->index()->comment('年级名称');
            $table->integer('parent_id')->nullable()->default(0)->index()->comment('父级id');
            $table->tinyInteger('sort')->nullable()->default(0)->comment('排序[0-255]');

            $table->unique(['grade_no', 'parent_id']);
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=1000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 1000");
        }

        // 在创建表之后，紧接着插入基础数据
        DB::table($this->name)->insertOrIgnoreReturning([
            ['id' => 1000, 'grade_no' => 1000, 'grade_name' => '幼儿园', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1001, 'grade_no' => 10001001, 'grade_name' => '小班', 'parent_id' => 1000, 'sort' => 1],
            ['id' => 1002, 'grade_no' => 10001002, 'grade_name' => '中班', 'parent_id' => 1000, 'sort' => 2],
            ['id' => 1003, 'grade_no' => 10001003, 'grade_name' => '大班', 'parent_id' => 1000, 'sort' => 3],
            ['id' => 1010, 'grade_no' => 1010, 'grade_name' => '小学', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1011, 'grade_no' => 10101011, 'grade_name' => '一年级', 'parent_id' => 1010, 'sort' => 1],
            ['id' => 1012, 'grade_no' => 10101012, 'grade_name' => '二年级', 'parent_id' => 1010, 'sort' => 2],
            ['id' => 1013, 'grade_no' => 10101013, 'grade_name' => '三年级', 'parent_id' => 1010, 'sort' => 3],
            ['id' => 1014, 'grade_no' => 10101014, 'grade_name' => '四年级', 'parent_id' => 1010, 'sort' => 4],
            ['id' => 1015, 'grade_no' => 10101015, 'grade_name' => '五年级', 'parent_id' => 1010, 'sort' => 5],
            ['id' => 1016, 'grade_no' => 10101016, 'grade_name' => '六年级', 'parent_id' => 1010, 'sort' => 6],
            ['id' => 1020, 'grade_no' => 1020, 'grade_name' => '初级中学', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1021, 'grade_no' => 10201021, 'grade_name' => '初中一年级', 'parent_id' => 1020, 'sort' => 1],
            ['id' => 1022, 'grade_no' => 10201022, 'grade_name' => '初中二年级', 'parent_id' => 1020, 'sort' => 2],
            ['id' => 1023, 'grade_no' => 10201023, 'grade_name' => '初中三年级', 'parent_id' => 1020, 'sort' => 3],
            ['id' => 1030, 'grade_no' => 1030, 'grade_name' => '高级中学', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1031, 'grade_no' => 10301031, 'grade_name' => '高中一年级', 'parent_id' => 1030, 'sort' => 1],
            ['id' => 1032, 'grade_no' => 10301032, 'grade_name' => '高中二年级', 'parent_id' => 1030, 'sort' => 2],
            ['id' => 1033, 'grade_no' => 10301033, 'grade_name' => '高中三年级', 'parent_id' => 1030, 'sort' => 3],
            ['id' => 1070, 'grade_no' => 1070, 'grade_name' => '职专', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1071, 'grade_no' => 10701071, 'grade_name' => '职专一年级', 'parent_id' => 1070, 'sort' => 1],
            ['id' => 1072, 'grade_no' => 10701072, 'grade_name' => '职专二年级', 'parent_id' => 1070, 'sort' => 2],
            ['id' => 1073, 'grade_no' => 10701073, 'grade_name' => '职专三年级', 'parent_id' => 1070, 'sort' => 3],
            ['id' => 1080, 'grade_no' => 1080, 'grade_name' => '大学', 'parent_id' => 0, 'sort' => 0],
            ['id' => 1081, 'grade_no' => 10801081, 'grade_name' => '大学一年级', 'parent_id' => 1080, 'sort' => 1],
            ['id' => 1082, 'grade_no' => 10801082, 'grade_name' => '大学二年级', 'parent_id' => 1080, 'sort' => 2],
            ['id' => 1083, 'grade_no' => 10801083, 'grade_name' => '大学三年级', 'parent_id' => 1080, 'sort' => 3],
            ['id' => 1084, 'grade_no' => 10801084, 'grade_name' => '大学四年级', 'parent_id' => 1080, 'sort' => 4],
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
