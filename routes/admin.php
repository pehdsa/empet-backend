<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BreedController;
use App\Http\Controllers\Admin\CharacteristicController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\PetController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SightingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Publico (login)
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    // Autenticado + admin
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('breeds', [BreedController::class, 'index'])->name('breeds.index');
        Route::post('breeds', [BreedController::class, 'store'])->name('breeds.store');
        Route::put('breeds/{breed}', [BreedController::class, 'update'])->name('breeds.update');
        Route::patch('breeds/{breed}/toggle-active', [BreedController::class, 'toggleActive'])->name('breeds.toggleActive');

        Route::get('characteristics', [CharacteristicController::class, 'index'])->name('characteristics.index');
        Route::post('characteristics', [CharacteristicController::class, 'store'])->name('characteristics.store');
        Route::put('characteristics/{characteristic}', [CharacteristicController::class, 'update'])->name('characteristics.update');
        Route::patch('characteristics/{characteristic}/toggle-active', [CharacteristicController::class, 'toggleActive'])->name('characteristics.toggleActive');

        Route::get('pets', [PetController::class, 'index'])->name('pets.index');
        Route::get('pets/{pet}', [PetController::class, 'show'])->name('pets.show');
        Route::patch('pets/{pet}/deactivate', [PetController::class, 'deactivate'])->name('pets.deactivate');
        Route::patch('pets/{pet}/reactivate', [PetController::class, 'reactivate'])->name('pets.reactivate');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::patch('reports/{report}/cancel', [ReportController::class, 'cancel'])->name('reports.cancel');
        Route::patch('reports/{report}/found', [ReportController::class, 'markFound'])->name('reports.found');

        Route::get('sightings', [SightingController::class, 'index'])->name('sightings.index');
        Route::get('sightings/{sighting}', [SightingController::class, 'show'])->name('sightings.show');
        Route::delete('sightings/{sighting}', [SightingController::class, 'destroy'])->name('sightings.destroy');

        Route::get('matches', [MatchController::class, 'index'])->name('matches.index');
        Route::get('matches/{match}', [MatchController::class, 'show'])->name('matches.show');
        Route::patch('matches/{match}/dismiss', [MatchController::class, 'dismiss'])->name('matches.dismiss');
    });
});
