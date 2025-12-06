<?php

use DagaSmart\Organization\Http\Controllers;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

Route::group([
    'prefix' => 'biz',
], function (Router $router) {
    $router->resource('enterprise/index', Controllers\EnterpriseController::class);
    $router->resource('enterprise/worker', Controllers\WorkerController::class);
    $router->resource('enterprise/facility', Controllers\FacilityController::class);
    $router->resource('enterprise/device', Controllers\DeviceController::class);

    $router->get('enterprise/{school_id}/facility/options', [Controllers\FacilityController::class, 'options']);
    $router->get('enterprise/{school_id}/facility/{id}/options', [Controllers\FacilityController::class, 'options']);

});

//一键导入文件
Route::post('enterprise/worker/import', [Controllers\WorkerController::class, 'import']);
Route::post('enterprise/student/import', [Controllers\StudentController::class, 'import']);
Route::post('enterprise/teacher/importChunk', [Controllers\TeacherController::class, 'importChunk']);

//删除导入文件
Route::post('enterprise/common/remove', [Controllers\CommonController::class, 'remove']);
