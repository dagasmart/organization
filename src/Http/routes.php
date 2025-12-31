<?php

use DagaSmart\Organization\Http\Controllers;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

Route::group([
    'prefix' => 'biz',
], function (Router $router) {
    $router->resource('enterprise/index', Controllers\EnterpriseController::class);
    $router->resource('enterprise/worker', Controllers\WorkerController::class);
    $router->resource('enterprise/student', Controllers\StudentController::class);
    $router->resource('enterprise/classes', Controllers\ClassesController::class);
    $router->resource('enterprise/facility', Controllers\FacilityController::class);
    $router->resource('enterprise/device', Controllers\DeviceController::class);

    $router->get('enterprise/{enterprise_id}/grade', [Controllers\GradeController::class, 'EnterpriseGrade']);
    $router->get('enterprise/{enterprise_id}/grade/{grade_id}/classes', [Controllers\ClassesController::class, 'enterpriseGradeClasses']);
    $router->get('enterprise/worker/{id_card}/check', [Controllers\WorkerController::class, 'EnterpriseWorkerCheck']);
    $router->get('enterprise/{enterprise_id}/facility/options', [Controllers\FacilityController::class, 'options']);
    $router->get('enterprise/{enterprise_id}/facility/{id}/options', [Controllers\FacilityController::class, 'options']);
    $router->get('enterprise/{enterprise_id}/facility/{facility_id}/device/{device_type}/options', [Controllers\DeviceController::class, 'deviceOptions']);
    $router->get('enterprise/device/{type}/brand/options', [Controllers\DeviceController::class, 'brandOptions']);
});

//一键导入文件
Route::post('enterprise/worker/import', [Controllers\WorkerController::class, 'import']);
Route::post('enterprise/student/import', [Controllers\StudentController::class, 'import']);
Route::post('enterprise/worker/importChunk', [Controllers\WorkerController::class, 'importChunk']);

//删除导入文件
Route::post('enterprise/common/remove', [Controllers\CommonController::class, 'remove']);
