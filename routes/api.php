<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ProgramController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Faculty Routes



Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('faculties', FacultyController::class);
    Route::post('faculties/{id}/restore', [FacultyController::class, 'restore']);
    Route::apiResource('programs', ProgramController::class);
    Route::post('programs/{id}/restore', [ProgramController::class, 'restore']);
    Route::post('/login', [AuthController::class, 'login']);
});

