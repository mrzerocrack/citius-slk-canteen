<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('get_data_tap_from_device', [App\Http\Controllers\MainController::class, 'get_data_tap_from_device']);