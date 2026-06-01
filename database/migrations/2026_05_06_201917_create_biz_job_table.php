<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school';

    private string $name = 'biz_job';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ! Schema::hasTable($this->name)
        && Schema::create($this->name, function (Blueprint $table) {
            $table->comment('数智校园-基础-职务表');
            $table->id();
            $table->string('job_name', 32)->nullable()->comment('职务名称');
            $table->string('tag', 100)->nullable()->comment('职务职责');
            $table->smallInteger('parent_id')->nullable()->comment('父id');
            $table->smallInteger('sort')->nullable()->comment('排序[0-255]');
        });

        // 在创建表之后，紧接着插入基础数据
        DB::table($this->name)->insertOrIgnoreReturning([
            ['id' => 1, 'job_name' => '行政类', 'tag' => '主要负责学校日常运营的管理工作', 'parent_id' => 1, 'sort' => 0],
            ['id' => 2, 'job_name' => '教学类', 'tag' => '主要负责教育教学工作', 'parent_id' => 2, 'sort' => 1],
            ['id' => 3, 'job_name' => '科研类', 'tag' => '主要从事科学研究工作', 'parent_id' => 3, 'sort' => 2],
            ['id' => 4, 'job_name' => '教辅类', 'tag' => '为教学和科研提供辅助支持', 'parent_id' => 4, 'sort' => 3],
            ['id' => 5, 'job_name' => '工勤类', 'tag' => '为学校的正常运转提供必要的支持服务', 'parent_id' => 5, 'sort' => 4],
            ['id' => 101, 'job_name' => '党支部书记', 'tag' => '协助校长处理日常事务，并负责党支部的日常工作', 'parent_id' => 101, 'sort' => 2],
            ['id' => 102, 'job_name' => '教学副校长', 'tag' => '主管学校的教育教学工作', 'parent_id' => 102, 'sort' => 3],
            ['id' => 103, 'job_name' => '科研副校长', 'tag' => '负责教育科研工作', 'parent_id' => 103, 'sort' => 4],
            ['id' => 104, 'job_name' => '德育副校长', 'tag' => '主管学生的思想政治工作', 'parent_id' => 104, 'sort' => 5],
            ['id' => 106, 'job_name' => '工会主席', 'tag' => '主持工会的各项工作', 'parent_id' => 106, 'sort' => 7],
            ['id' => 107, 'job_name' => '办公室主任', 'tag' => '协助校长处理学校的日常行政事务', 'parent_id' => 107, 'sort' => 8],
            ['id' => 108, 'job_name' => '团委书记', 'tag' => '负责学校团组织的各项工作', 'parent_id' => 108, 'sort' => 9],
            ['id' => 109, 'job_name' => '人事处长', 'tag' => '负责师资引进和教师考核工作', 'parent_id' => 109, 'sort' => 10],
            ['id' => 110, 'job_name' => '财务处长', 'tag' => '负责师资引进和教师考核工作', 'parent_id' => 110, 'sort' => 11],
            ['id' => 111, 'job_name' => '教导处主任', 'tag' => '主管教育教学工作', 'parent_id' => 111, 'sort' => 12],
            ['id' => 112, 'job_name' => '教导处副主任', 'tag' => '分别负责语文、数学和综合学科的教学工作', 'parent_id' => 112, 'sort' => 13],
            ['id' => 113, 'job_name' => '德育处主任', 'tag' => '主管班主任和学生思想政治工作', 'parent_id' => 113, 'sort' => 14],
            ['id' => 114, 'job_name' => '德育副主任', 'tag' => '负责少先队工作', 'parent_id' => 114, 'sort' => 15],
            ['id' => 115, 'job_name' => '总务主任', 'tag' => '主管学校后勤工作', 'parent_id' => 115, 'sort' => 16],
            ['id' => 116, 'job_name' => '总务副主任', 'tag' => '负责学校财务管理工作', 'parent_id' => 116, 'sort' => 17],
            ['id' => 117, 'job_name' => '教科室主任', 'tag' => '主管学校的教科研工作', 'parent_id' => 117, 'sort' => 18],
            ['id' => 118, 'job_name' => '教科室副主任', 'tag' => '负责学校的课程建设', 'parent_id' => 118, 'sort' => 19],
            ['id' => 200, 'job_name' => '教务主任', 'tag' => '负责组织和管理学校的教学工作', 'parent_id' => 200, 'sort' => 2],
            ['id' => 201, 'job_name' => '教研组长', 'tag' => '统筹学科教学计划制定与实施、组织集体备课与教学研讨活动', 'parent_id' => 201, 'sort' => 3],
            ['id' => 202, 'job_name' => '年级组长', 'tag' => '全面管理本年级的教育教学活动', 'parent_id' => 202, 'sort' => 4],
            ['id' => 203, 'job_name' => '班主任', 'tag' => '负责学生的全面教育和管理工作', 'parent_id' => 203, 'sort' => 5],
            ['id' => 204, 'job_name' => '任课教师', 'tag' => '按教学计划和课程标准组织教学', 'parent_id' => 204, 'sort' => 6],
            ['id' => 300, 'job_name' => '研究所长', 'tag' => '引领研究所的发展并推动科学研究的进步', 'parent_id' => 300, 'sort' => 3],
            ['id' => 301, 'job_name' => '实验室主任', 'tag' => '全面负责实验室的建设、管理和运行工作', 'parent_id' => 301, 'sort' => 4],
            ['id' => 302, 'job_name' => '课题组组长', 'tag' => '全面负责课题的规划、实施和管理工作', 'parent_id' => 302, 'sort' => 5],
            ['id' => 303, 'job_name' => '科研助理', 'tag' => '为科研项目提供实验技术支持、数据采集与分析', 'parent_id' => 303, 'sort' => 6],
            ['id' => 400, 'job_name' => '图书馆长', 'tag' => '全面负责图书馆的规划、建设与管理工作', 'parent_id' => 400, 'sort' => 4],
            ['id' => 401, 'job_name' => '阅览室管理员', 'tag' => '负责维护阅览室的秩序，管理图书资源', 'parent_id' => 401, 'sort' => 5],
            ['id' => 402, 'job_name' => '实验室管理员', 'tag' => '负责实验室的日常运作、设备维护、安全管理及教学支持', 'parent_id' => 402, 'sort' => 6],
            ['id' => 403, 'job_name' => '资料室管理员', 'tag' => '负责资料的收集、整理、保管和提供服务', 'parent_id' => 403, 'sort' => 7],
            ['id' => 500, 'job_name' => '校医', 'tag' => '全面负责学校的日常医疗保健、健康教育与宣教', 'parent_id' => 500, 'sort' => 5],
            ['id' => 501, 'job_name' => '心理咨询师', 'tag' => '负责学生心理健康教育和心理辅导', 'parent_id' => 501, 'sort' => 6],
            ['id' => 502, 'job_name' => '网络管理员', 'tag' => '主管学校的网络建设与安全维护工作', 'parent_id' => 502, 'sort' => 7],
            ['id' => 503, 'job_name' => '保安', 'tag' => '负责人员、车辆、物品出入，治安巡逻管理工作', 'parent_id' => 503, 'sort' => 8],
            ['id' => 504, 'job_name' => '保洁员', 'tag' => '主管学校日常清洁卫生工作', 'parent_id' => 504, 'sort' => 9],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->name)) {
            // 检查是否存在数据
            // $exists = DB::table($this->name)->exists();
            // 不存在数据时，删除表
            // if (! $exists) {
            // 删除 reverse
            Schema::dropIfExists($this->name);
            // }
        }
    }
};
