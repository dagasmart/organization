<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\Organization\Enums\Enum;
use DagaSmart\Organization\Services\ClassesService;
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
     * 机构年级班级列表
     * @param $enterprise_id
     * @param $grade_id
     * @return array
     */
    public function enterpriseGradeClasses($enterprise_id, $grade_id): array
    {
        return $this->service->enterpriseGradeClasses($enterprise_id, $grade_id);

    }


}
