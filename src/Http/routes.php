<?php

use DagaSmart\BizAdmin\Middleware\Authenticate;
use DagaSmart\BizAdmin\Middleware\Permission;
use DagaSmart\Organization\Http\Controllers;
use DagaSmart\Organization\Http\Middleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'extension',
    'middleware' => [Middleware\Middleware::class],
], function (Router $router) {

    $router->get('enterprise/chart/data', [Controllers\EnterpriseController::class, 'chartData'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{social_credit_code}/check', [Controllers\EnterpriseController::class, 'enterpriseCheck'])->withoutMiddleware([Permission::class]);

    $router->delete('enterprise/job/{id}/delete', [Controllers\EnterpriseController::class, 'jobDelete'])->withoutMiddleware([Permission::class]);
    $router->delete('enterprise/department/{id}/delete', [Controllers\EnterpriseController::class, 'departmentDelete'])->withoutMiddleware([Permission::class]);
    $router->post('enterprise/department/save', [Controllers\EnterpriseController::class, 'departmentSave'])->withoutMiddleware([Permission::class]);
    $router->post('enterprise/job/save', [Controllers\EnterpriseController::class, 'jobSave'])->withoutMiddleware([Permission::class]);

    $router->get('enterprise/{enterprise_id}/department/data', [Controllers\EnterpriseController::class, 'departmentData'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/job/data', [Controllers\EnterpriseController::class, 'jobData'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/department/{department_id}/job/data', [Controllers\EnterpriseController::class, 'departmentJobData'])->withoutMiddleware([Permission::class]);

    $router->get('enterprise/nature/{stage_id}/option', [Controllers\EnterpriseController::class, 'natureOption'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/stage/{nature_id}/option', [Controllers\EnterpriseController::class, 'stageOption'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/stage/{stage_id}/grade/all', [Controllers\EnterpriseController::class, 'getGradeAll'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/grade', [Controllers\GradeController::class, 'EnterpriseGrade'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/grade/{grade_id}/classes', [Controllers\ClassesController::class, 'enterpriseGradeClasses'])->withoutMiddleware([Permission::class]);

    $router->get('worker/{enterprise_id}/department/data', [Controllers\WorkerController::class, 'departmentData'])->withoutMiddleware([Permission::class]);
    $router->get('worker/{enterprise_id}/job/data', [Controllers\WorkerController::class, 'jobData'])->withoutMiddleware([Permission::class]);
    $router->get('worker/{enterprise_id}/department/{department_id}/job/data', [Controllers\WorkerController::class, 'departmentJobData'])->withoutMiddleware([Permission::class]);

    $router->get('enterprise/worker/{id_card}/check', [Controllers\WorkerController::class, 'EnterpriseWorkerCheck'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/patriarch/{id_card}/check', [Controllers\PatriarchController::class, 'EnterprisePatriarchCheck'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/facility/options', [Controllers\FacilityController::class, 'options'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/facility/{id}/options', [Controllers\FacilityController::class, 'options'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/facility/{facility_id}/device/{device_type}/options', [Controllers\DeviceController::class, 'deviceOptions'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/{enterprise_id}/facility/{facility_id}/device/{device_type}/brand/{device_brand}/options', [Controllers\DeviceController::class, 'deviceOptions'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/device/{type}/brand/options', [Controllers\DeviceController::class, 'brandOptions'])->withoutMiddleware([Permission::class]);

    $router->get('enterprise/student/search', [Controllers\StudentController::class, 'search'])->withoutMiddleware([Permission::class]);
    $router->get('enterprise/student/{id_card}/check', [Controllers\StudentController::class, 'enterpriseStudentCheck'])->withoutMiddleware([Permission::class]);

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
