<?php

namespace DagaSmart\School\Models;

use DagaSmart\School\Enums\Enum;

/**
 * 基础-职务类
 */
class Job extends Model
{
	protected $table = 'biz_job';
    protected $primaryKey = 'id';

    public $timestamps = false;


    /**
     * 初始化职务数据
     * @return void
     */
    public static function initialize(): void
    {
        static::query()->truncate();
        if ($jobs = Enum::job()) {
            foreach ($jobs as $k => $job) {
                $data = [
                    'id' => $k + 1,
                    'job_name' => $job['label'],
                    'tag' => $job['tag'],
                    'parent_id' => 0,
                    'sort' => $k++,
                ];
                $id = Job::query()->insertGetId($data);
                foreach ($job['children'] as $child) {
                    $ins = [
                        'id' => $child['value'],
                        'job_name' => $child['label'],
                        'tag' => $child['tag'],
                        'parent_id' => $id,
                        'sort' => $k++
                    ];
                    Job::query()->insert($ins);
                }
            }
        }
    }


}
