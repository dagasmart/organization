<?php

use DagaSmart\Organization\Http\Controllers;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'biz',
], function (Router $router) {

    $router->get('enterprise/{enterprise_id}/grade', [Controllers\GradeController::class, 'EnterpriseGrade']);
    $router->get('enterprise/{enterprise_id}/grade/{grade_id}/classes', [Controllers\ClassesController::class, 'enterpriseGradeClasses']);
    $router->put('enterprise/{enterprise_id}/department/save', [Controllers\EnterpriseController::class, 'departmentSave']);
    $router->put('enterprise/{enterprise_id}/job/save', [Controllers\EnterpriseController::class, 'jobSave']);
    $router->get('enterprise/stage/{nature_id}/option', [Controllers\EnterpriseController::class, 'stageOption']);
    $router->get('enterprise/worker/{id_card}/check', [Controllers\WorkerController::class, 'EnterpriseWorkerCheck']);
    $router->get('enterprise/patriarch/{id_card}/check', [Controllers\PatriarchController::class, 'EnterprisePatriarchCheck']);
    $router->get('enterprise/{enterprise_id}/facility/options', [Controllers\FacilityController::class, 'options']);
    $router->get('enterprise/{enterprise_id}/facility/{id}/options', [Controllers\FacilityController::class, 'options']);
    $router->get('enterprise/{enterprise_id}/facility/{facility_id}/device/{device_type}/options', [Controllers\DeviceController::class, 'deviceOptions']);
    $router->get('enterprise/{enterprise_id}/facility/{facility_id}/device/{device_type}/brand/{device_brand}/options', [Controllers\DeviceController::class, 'deviceOptions']);
    $router->get('enterprise/device/{type}/brand/options', [Controllers\DeviceController::class, 'brandOptions']);

    $router->resource('enterprise/index', Controllers\EnterpriseController::class);
    $router->resource('enterprise/worker', Controllers\WorkerController::class);
    $router->resource('enterprise/student', Controllers\StudentController::class);
    $router->resource('enterprise/patriarch', Controllers\PatriarchController::class);
    $router->resource('enterprise/classes', Controllers\ClassesController::class);
    $router->resource('enterprise/facility', Controllers\FacilityController::class);
    $router->resource('enterprise/device', Controllers\DeviceController::class);
});

// 一键导入文件
Route::post('enterprise/worker/import', [Controllers\WorkerController::class, 'import']);
Route::post('enterprise/student/import', [Controllers\StudentController::class, 'import']);
Route::post('enterprise/worker/importChunk', [Controllers\WorkerController::class, 'importChunk']);

// 删除导入文件
Route::post('enterprise/common/remove', [Controllers\CommonController::class, 'remove']);
