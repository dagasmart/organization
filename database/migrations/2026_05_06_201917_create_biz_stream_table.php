<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'school';
    private string $name = 'biz_stream';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        !Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-推拉流表');
            $table->id();
            $table->string('title', 32)->nullable()->comment('名称');
            $table->string('agree', 32)->nullable()->comment('协议（源）');
            $table->string('remote')->nullable()->comment('输入流（源）');
            $table->string('name', 32)->nullable()->comment('输出流名称');
            $table->string('url')->nullable()->comment('输出流地址');
            $table->string('fix', 32)->nullable()->comment('输出流格式');
            $table->string('sip', 32)->nullable()->default('127.0.0.1')->comment('分流内网IP');
            $table->tinyInteger('port')->nullable()->default(8000)->comment('分流公网端口');
            $table->string('piont', 32)->nullable()->comment('经纬度');
            $table->tinyInteger('sort')->nullable()->default(10)->comment('排序[0-255]');
            $table->tinyInteger('state')->nullable()->default(0)->comment('状态：0禁用，1启用');
            $table->tinyInteger('hot')->nullable()->default(0)->comment('热点：1是， 0否');
            $table->tinyInteger('top')->nullable()->default(0)->comment('置顶：1是，0否');
            $table->tinyInteger('online')->nullable()->default(0)->comment('1在线，0离线');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        $driver = config('database.connections.'.$this->connection.'.driver');
        if ($driver == 'mysql') {
            DB::statement("ALTER TABLE {$this->name} AUTO_INCREMENT=100000");
        }
        if ($driver == 'pgsql') {
            DB::statement("alter sequence {$this->name}_id_seq restart with 100000");
        }
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
