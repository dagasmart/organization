<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('school')->create('biz_enterprise_department_job_worker', function (Blueprint $table) {
            $table->comment('数智校园-机构-部门-职务-员工关联表');
            $table->integer('enterprise_id')->comment('机构id');
            $table->integer('department_id')->comment('部门id');
            $table->integer('job_id')->comment('职务id');
            $table->integer('worker_id')->comment('员工id');
            $table->string('worker_sn', 32)->nullable()->comment('工号');
            $table->string('module', 32)->nullable();
            $table->integer('mer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school')->dropIfExists('biz_enterprise_department_job_worker');
    }
};
