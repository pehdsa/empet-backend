<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CharacteristicController;
use App\Http\Controllers\Api\V1\PetController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::put('auth/password', [AuthController::class, 'changePassword']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('pets', PetController::class);
    Route::patch('pets/{pet}/toggle-active', [PetController::class, 'toggleActive']);

    Route::get('characteristics', [CharacteristicController::class, 'index']);
});
