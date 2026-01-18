<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| All routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Current user (authenticated)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API V1 Routes
Route::prefix('v1')->group(base_path('routes/api/v1.php'));
