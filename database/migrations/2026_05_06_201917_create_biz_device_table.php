<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $table = 'biz_device';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->table)
        && Schema::create($this->table, function (Blueprint $table) {
            $table->comment('数智校园-基础-设备表');
            $table->id();
            $table->string('device_name', 32)->nullable()->comment('设备名称');
            $table->string('device_sn', 64)->nullable()->comment('设备编号');
            $table->string('device_model', 32)->nullable()->comment('设备型号');
            $table->string('device_brand', 16)->nullable()->comment('设备品牌');
            $table->string('device_type', 20)->nullable()->comment('设备类型');
            $table->string('device_pos', 16)->nullable()->comment('安装位置');
            $table->decimal('market_price', 10)->default(0)->comment('零售价');
            $table->tinyInteger('sort')->default(10)->comment('排序');
            $table->tinyInteger('state')->default(1)->comment('状态');
            $table->string('device_desc')->nullable()->comment('设备描述');
            $table->tinyInteger('online')->default(0)->comment('设备状态');
            $table->text('images')->nullable()->comment('设备图片');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();

            // ✅ 2. 仅为级联删除和外键查询创建【单列】索引
            // 联合索引的最左前缀原则无法高效支持中间列的等值查询/级联删除
            $table->index('device_type');
            $table->index('device_sn');
            $table->index('state');

            // ✅ 3. 唯一约束即主查询索引，框架自动生成 ≤63 字节安全名称
            $table->unique(['device_type', 'device_sn']);
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=1000000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 1000000");
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            //检查是否存在数据
            $exists = DB::table($this->table)->exists();
            //不存在数据时，删除表
            if (!$exists) {
                //删除 reverse
                Schema::dropIfExists($this->table);
            }
        }
    }
};
