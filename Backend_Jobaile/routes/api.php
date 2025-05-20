<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Profilecontroller;

Route::post('/registerrecruiter', [AuthController::class, 'registerRecruiter']); // bisa
Route::post('/registerworker', [AuthController::class, 'registerWorker']); // bisa
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [Profilecontroller::class, 'getProfile']); // bisa
});

