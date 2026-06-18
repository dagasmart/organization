<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\Organization\Services\ClassesService;

/**
 * 基础-班级类
 *
 * @property ClassesService $service
 */
class ClassesController extends AdminController
{
    protected string $serviceName = ClassesService::class;

    public function list(): Page
    {
        return $this->baseList([]);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([])->static();
    }

    /**
     * 机构年级班级列表
     */
    public function enterpriseGradeClasses($enterprise_id, $grade_id): array
    {
        return $this->service->enterpriseGradeClasses($enterprise_id, $grade_id);

    }
}
