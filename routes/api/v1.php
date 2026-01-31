<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Borrowing\BorrowingController;
use App\Http\Controllers\Admin\Dashboard\ArchivistDashboardController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Location\CabinetController;
use App\Http\Controllers\Admin\Location\DrawerController;
use App\Http\Controllers\Admin\Academic\FacultyController;
use App\Http\Controllers\Admin\Academic\ProgramController;
use App\Http\Controllers\Admin\Location\RoomController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\Student\DocumentTypeController;
use App\Http\Controllers\Admin\Student\StudentController;
use App\Http\Controllers\Admin\Student\StudentDocumentController;
use App\Http\Controllers\Admin\Upload\TempUploadController;
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

    // Dashboard (admin only)
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Archivist Dashboard
    Route::get('dashboard/archivist', [ArchivistDashboardController::class, 'index']);

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

    // Students
    Route::apiResource('students', StudentController::class);
    Route::apiResource('student-documents', StudentDocumentController::class);
    Route::apiResource('document-types', DocumentTypeController::class);

    // Borrowing System
    Route::prefix('borrowings')->group(function () {
        Route::get('/', [BorrowingController::class, 'index']);
        Route::post('/', [BorrowingController::class, 'store']);
        Route::get('/{id}', [BorrowingController::class, 'show']);
        Route::patch('/{id}', [BorrowingController::class, 'update']);
        Route::delete('/{id}', [BorrowingController::class, 'destroy']);
        
        // Special workflow actions
        Route::post('/{id}/approve', [BorrowingController::class, 'approve']);
        Route::post('/{id}/mark-borrowed', [BorrowingController::class, 'markBorrowed']);
        Route::post('/{id}/return', [BorrowingController::class, 'return']);
    });

    // Temp Uploads
    Route::post('uploads', [TempUploadController::class, 'store']);
    Route::delete('uploads/{id}', [TempUploadController::class, 'destroy']);
});

