<?php

use DagaSmart\Organization\Http\Controllers;
use DagaSmart\Organization\Http\Middleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'biz',
    'middleware' => [Middleware\Middleware::class],
], function (Router $router) {

    $router->delete('enterprise/job/{id}/delete', [Controllers\EnterpriseController::class, 'jobDelete']);
    $router->delete('enterprise/department/{id}/delete', [Controllers\EnterpriseController::class, 'departmentDelete']);
    $router->post('enterprise/department/save', [Controllers\EnterpriseController::class, 'departmentSave']);
    $router->post('enterprise/job/save', [Controllers\EnterpriseController::class, 'jobSave']);

    $router->get('enterprise/{enterprise_id}/department/data', [Controllers\EnterpriseController::class, 'departmentData']);
    $router->get('enterprise/{enterprise_id}/job/data', [Controllers\EnterpriseController::class, 'jobData']);
    $router->get('enterprise/{enterprise_id}/department/{department_id}/job/data', [Controllers\EnterpriseController::class, 'departmentJobData']);

    $router->get('enterprise/stage/{nature_id}/option', [Controllers\EnterpriseController::class, 'stageOption']);
    $router->get('enterprise/stage/{stage_id}/grade/all', [Controllers\EnterpriseController::class, 'getGradeAll']);
    $router->get('enterprise/{enterprise_id}/grade', [Controllers\GradeController::class, 'EnterpriseGrade']);
    $router->get('enterprise/{enterprise_id}/grade/{grade_id}/classes', [Controllers\ClassesController::class, 'enterpriseGradeClasses']);

    $router->get('worker/{enterprise_id}/department/data', [Controllers\WorkerController::class, 'departmentData']);
    $router->get('worker/{enterprise_id}/job/data', [Controllers\WorkerController::class, 'jobData']);
    $router->get('worker/{enterprise_id}/department/{department_id}/job/data', [Controllers\WorkerController::class, 'departmentJobData']);

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
