<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $table = 'biz_device_activity';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->table)
        && Schema::create($this->table, function (Blueprint $table) {
            $table->comment('数智校园-基础-设备活检表');
            $table->id();
            $table->integer('device_id')->nullable()->index($this->table.'_device_id_idx')->comment('设备id');
            $table->string('device_sn', 32)->index($this->table.'_device_sn_idx')->comment('设备编码');
            $table->string('device_type', 20)->nullable()->index($this->table.'_device_type_idx')->comment('设备类型');
            $table->string('device_mac', 32)->nullable()->comment('MAC');
            $table->timestamp('device_time')->nullable()->index($this->table.'_device_time_idx')->comment('响应时间');
            $table->json('device_res')->nullable()->comment('响应结果');
            $table->string('device_version', 32)->nullable()->comment('版本号');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();

            $table->unique(['device_sn', 'device_type']);
            $table->index(['device_sn', 'device_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            // 检查是否存在数据
            $exists = DB::table($this->table)->exists();
            // 不存在数据时，删除表
            if (! $exists) {
                // 删除 reverse
                Schema::dropIfExists($this->table);
            }
        }
    }
};
