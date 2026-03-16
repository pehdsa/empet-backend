<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BreedController;
use App\Http\Controllers\Api\V1\CharacteristicController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\PetReportController;
use App\Http\Controllers\Api\V1\UserPhoneController;
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

    Route::apiResource('pet-reports', PetReportController::class)->except(['destroy']);
    Route::patch('pet-reports/{petReport}/cancel', [PetReportController::class, 'cancel']);
    Route::patch('pet-reports/{petReport}/found', [PetReportController::class, 'markFound']);
    Route::get('pet-reports/{petReport}/matches', [PetReportController::class, 'matches']);
    Route::patch('pet-reports/{petReport}/matches/{petMatch}/dismiss', [PetReportController::class, 'dismissMatch']);
    Route::patch('pet-reports/{petReport}/matches/{petMatch}/confirm', [PetReportController::class, 'confirmMatch']);

    Route::prefix('user/phones')->group(function (): void {
        Route::get('/', [UserPhoneController::class, 'index']);
        Route::post('/', [UserPhoneController::class, 'store']);
        Route::put('/{id}', [UserPhoneController::class, 'update']);
        Route::delete('/{id}', [UserPhoneController::class, 'destroy']);
    });

    Route::get('breeds', [BreedController::class, 'index']);
    Route::get('characteristics', [CharacteristicController::class, 'index']);
});
