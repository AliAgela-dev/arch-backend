<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\CabinetController;
use App\Http\Controllers\Api\DrawerController;

Route::apiResource('rooms', RoomController::class);
Route::apiResource('cabinets', CabinetController::class);
Route::apiResource('drawers', DrawerController::class);
