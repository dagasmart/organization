<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_enterprise_grade_classes';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-年级-班级关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('grade_id')->comment('年级id');
            $table->integer('classes_id')->comment('班级id');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();

            $table->unique(['enterprise_id', 'grade_id', 'classes_id', 'module', 'mer_id']);
            $table->index(['enterprise_id', 'grade_id', 'classes_id', 'module', 'mer_id']);
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
