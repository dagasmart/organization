<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\Organization\Services\GradeService;

/**
 * 基础-年级类
 *
 * @property GradeService $service
 */
class GradeController extends AdminController
{
	protected string $serviceName = GradeService::class;

    /**
     * 机构年级列表
     * @param $school_id
     * @return array
     */
    public function EnterpriseGrade($school_id): array
    {
        return $this->service->EnterpriseGrade($school_id);

    }



}
