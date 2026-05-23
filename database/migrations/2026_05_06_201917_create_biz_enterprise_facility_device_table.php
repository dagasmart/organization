<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_enterprise_facility_device';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-机构-设施-设备关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('facility_id')->comment('设施id');
            $table->integer('device_id')->comment('设备id');
            $table->string('module', 32)->nullable()->comment('模块');
            $table->integer('mer_id')->nullable()->comment('商户');

            $unique = ['enterprise_id', 'facility_id', 'device_id', 'module', 'mer_id'];
            $uni = $this->name . '_';
            $uni .= implode('_', $unique);
            $uni .= '_unique';
            $unique_name = mb_strlen($uni) > 64 ? md5($uni) : $uni;
            $table->unique($unique, $unique_name);

            $index = ['enterprise_id', 'facility_id', 'device_id', 'module', 'mer_id'];
            $idx = $this->name . '_';
            $idx .= implode('_', $index);
            $idx .= '_idx';
            $index_name = mb_strlen($idx) > 64 ? md5($idx) : $idx;
            $table->index($index, $index_name);

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
