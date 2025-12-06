<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Enums\Enum;
use DagaSmart\School\Services\ClassesService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;

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
     * 学校年级班级列表
     * @param $school_id
     * @param $grade_id
     * @return array
     */
    public function SchoolGradeClasses($school_id, $grade_id): array
    {
        return $this->service->SchoolGradeClasses($school_id, $grade_id);

    }


}
