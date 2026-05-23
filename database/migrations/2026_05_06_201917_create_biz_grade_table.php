<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_grade';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-年级表');
            $table->id();
            $table->tinyInteger('grade_no')->nullable()->index()->comment('年级编号');
            $table->string('grade_name', 20)->nullable()->index()->comment('年级名称');
            $table->integer('parent_id')->nullable()->default(0)->index()->comment('父级id');
            $table->tinyInteger('sort')->nullable()->default(0)->comment('排序[0-255]');

            $table->unique(['grade_no', 'parent_id']);
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
