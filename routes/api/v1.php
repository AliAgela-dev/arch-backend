<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Location\CabinetController;
use App\Http\Controllers\Admin\Location\DrawerController;
use App\Http\Controllers\Admin\Academic\FacultyController;
use App\Http\Controllers\Admin\Academic\ProgramController;
use App\Http\Controllers\Admin\Location\RoomController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users
    Route::apiResource('users', UserController::class);

    // Academic
    Route::prefix('academic')->group(function () {
        Route::apiResource('faculties', FacultyController::class);
        Route::post('faculties/{id}/restore', [FacultyController::class, 'restore']);
        
        Route::apiResource('programs', ProgramController::class);
        Route::post('programs/{id}/restore', [ProgramController::class, 'restore']);
    });

    // Location (Archive)
    Route::prefix('location')->group(function () {
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('cabinets', CabinetController::class);
        Route::apiResource('drawers', DrawerController::class);
    });
});
