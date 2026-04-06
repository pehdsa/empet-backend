<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Publico (admin guest)
    Route::middleware('guest')->group(function () {
        // Auth routes will be added in PR 1B
    });

    // Autenticado + admin
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
});
