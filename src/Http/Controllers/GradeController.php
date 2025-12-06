<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\School\Enums\Enum;
use DagaSmart\School\Services\GradeService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * 基础-年级类
 *
 * @property GradeService $service
 */
class GradeController extends AdminController
{
	protected string $serviceName = GradeService::class;

    /**
     * 学校年级列表
     * @param $school_id
     * @return array
     */
    public function SchoolGrade($school_id): array
    {
        return $this->service->SchoolGrade($school_id);

    }



}
