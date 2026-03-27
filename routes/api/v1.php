<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BreedController;
use App\Http\Controllers\Api\V1\CharacteristicController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationSettingController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\PetReportController;
use App\Http\Controllers\Api\V1\PetSightingController;
use App\Http\Controllers\Api\V1\UserDeviceController;
use App\Http\Controllers\Api\V1\UserPhoneController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:forgot-password');

Route::post('auth/verify-reset-code', [AuthController::class, 'verifyResetCode'])
    ->middleware('throttle:verify-reset-code');

Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:reset-password');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/user', [AuthController::class, 'user']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('pets', PetController::class);
    Route::patch('pets/{pet}/toggle-active', [PetController::class, 'toggleActive']);

    Route::get('pet-reports/lost', [PetReportController::class, 'lost']);
    Route::get('pet-reports/found', [PetReportController::class, 'found']);
    Route::get('pet-reports/{petReport}/detail', [PetReportController::class, 'detail']);

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

    // Notification Settings
    Route::get('user/notification-settings', [NotificationSettingController::class, 'show']);
    Route::put('user/notification-settings', [NotificationSettingController::class, 'update']);

    // User Devices
    Route::apiResource('user/devices', UserDeviceController::class)
        ->only(['index', 'store', 'destroy'])
        ->parameters(['devices' => 'userDevice']);

    // Notifications (read-all BEFORE {notification}/read to avoid parameter capture)
    Route::get('user/notifications', [NotificationController::class, 'index']);
    Route::patch('user/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('user/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::get('user/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Pet Sightings (nested under pet-reports)
    Route::get('pet-reports/{petReport}/sightings', [PetSightingController::class, 'index']);
    Route::post('pet-reports/{petReport}/sightings', [PetSightingController::class, 'store']);
    Route::get('pet-reports/{petReport}/sightings/{petSighting}', [PetSightingController::class, 'show']);

    Route::get('breeds', [BreedController::class, 'index']);
    Route::get('characteristics', [CharacteristicController::class, 'index']);
});
