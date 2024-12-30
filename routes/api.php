<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('get_data_tap_from_device', [App\Http\Controllers\MainController::class, 'get_data_tap_from_device']);
Route::get('generate_canteen_card_all_employee', [App\Http\Controllers\CanteenController::class, 'generate_card_all_employee']);
Route::post('sync_canteen', [App\Http\Controllers\CanteenController::class, 'sync_canteen']);
Route::post('get_last_canteen_trx', [App\Http\Controllers\CanteenController::class, 'get_last_canteen_trx']);
Route::post('upload_canteen_trx', [App\Http\Controllers\CanteenController::class, 'upload_canteen_trx']);
Route::post('get_unpull_slp_trx', [App\Http\Controllers\CanteenController::class, 'get_unpull_slp_trx']);
Route::post('sync_slp', [App\Http\Controllers\CanteenController::class, 'sync_slp']);
Route::post('get_unpull_employee_trx', [App\Http\Controllers\CanteenController::class, 'get_unpull_employee_trx']);
Route::post('sync_employee', [App\Http\Controllers\CanteenController::class, 'sync_employee']);
Route::post('get_unpull_employee_cc_trx', [App\Http\Controllers\CanteenController::class, 'get_unpull_employee_cc_trx']);
Route::post('sync_employee_cc', [App\Http\Controllers\CanteenController::class, 'sync_employee_cc']);
Route::post('sync_log', [App\Http\Controllers\CanteenController::class, 'sync_log']);
Route::post('get_last_log', [App\Http\Controllers\CanteenController::class, 'get_last_log']);
Route::post('upload_log', [App\Http\Controllers\CanteenController::class, 'upload_log']);
Route::post('get_gatepass_data_by_filter', [App\Http\Controllers\GatePassController::class, 'get_gatepass_data_by_filter']);
Route::get('get_unassigned_employee_autocomplete', [App\Http\Controllers\EventAttendanceController::class, 'get_unassigned_employee_autocomplete']);
Route::get('get_employee_data_by_filter', [App\Http\Controllers\EmployeeController::class, 'get_employee_data_by_filter']);
Route::get('get_pic_employee_autocomplete', [App\Http\Controllers\HoicardController::class, 'get_pic_employee_autocomplete']);
Route::get('get_employee_autocomplete', [App\Http\Controllers\EmployeeController::class, 'get_employee_autocomplete']);
Route::post('employee_data_first_by_id', [App\Http\Controllers\EmployeeController::class, 'employee_data_first_by_id']);