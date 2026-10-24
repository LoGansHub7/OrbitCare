<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReadingController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Health check (no sensitive details)
Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => 'OrbitCare']))->name('health');

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->isAstronaut() ? 'dashboard' : 'staff.home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Medical officer / admin area (built out in later phases)
    Route::middleware(EnsureRole::class.':medical_officer,admin')->group(function () {
        Route::view('/staff', 'staff.home')->name('staff.home');
    });

    // Astronaut area: every query is scoped to the signed-in user's own crew profile
    Route::middleware(EnsureRole::class.':astronaut')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/check-in', [CheckinController::class, 'create'])->name('checkin.create');
        Route::post('/check-in', [CheckinController::class, 'store'])->name('checkin.store');
        Route::get('/readings', [ReadingController::class, 'index'])->name('readings.index');
        Route::post('/readings', [ReadingController::class, 'store'])->name('readings.store');
    });
});
