<?php

namespace DagaSmart\Organization\Http\Controllers;

use DagaSmart\Organization\Services\CommonService;
use Fiber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

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
