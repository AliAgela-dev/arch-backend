<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ProgramController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Faculty Routes
Route::apiResource('faculties', FacultyController::class);
Route::post('faculties/{id}/restore', [FacultyController::class, 'restore']);

// Program Routes
Route::apiResource('programs', ProgramController::class);
Route::post('programs/{id}/restore', [ProgramController::class, 'restore']);
