<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Student;
/**
 * 基础-用户服务类
 */
class UserService extends AdminService
{

    /**
     * 班级列表
     * @return array
     */
    public function userAll(): array
    {
        $model = new Student;
        return $model->query()->get(['id as value','classes_name as label'])->toArray();
    }

}
