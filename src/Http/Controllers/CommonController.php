<?php

namespace DagaSmart\School\Http\Controllers;

use DagaSmart\School\Services\CommonService;
use DagaSmart\BizAdmin\Controllers\AdminController;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Support\Cores\AdminPipeline;
use Fiber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * 公共类
 *
 * @property CommonService $service
 */
class CommonController extends AdminController
{
	protected string $serviceName = CommonService::class;

    public function remove(): JsonResponse|JsonResource
    {
        try {
            $fiber = new Fiber(function(){
                $path = request()->path;
                @unlink(public_storage_path('storage' . DIRECTORY_SEPARATOR . $path));
            });
            $fiber->start();
            return $this->response()->success([],'已删除');
        } catch (\Throwable $e) {
            return $this->response()->fail('删除失败');
        }
    }


}
