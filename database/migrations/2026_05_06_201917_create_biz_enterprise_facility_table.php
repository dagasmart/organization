<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_enterprise_facility';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-设施关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('facility_id')->comment('设施id');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();

            $table->unique(['enterprise_id', 'facility_id', 'module', 'mer_id']);
            $table->index(['enterprise_id', 'facility_id', 'module', 'mer_id']);
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
