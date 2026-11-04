<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\CrewOverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\AdminController;
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

Route::get('/_components', function () {
    abort_unless(app()->environment('local'), 404);
    return view('components.preview');
})->name('components.preview');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/research', [ReportController::class, 'research'])->name('research.index');

    // Medical officer / admin area (built out in later phases)
    Route::middleware(EnsureRole::class.':medical_officer,admin')->group(function () {
        Route::get('/staff', [CrewOverviewController::class, 'index'])->name('staff.home');
    });

    Route::middleware(EnsureRole::class.':admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::patch('/missions/{mission}/earth-link', [AdminController::class, 'updateMissionLink'])->name('missions.link');
    });

    Route::middleware(EnsureRole::class.':medical_officer')->group(function () {
        Route::get('/staff/crew/{crewProfileId}', [CrewOverviewController::class, 'show'])
            ->whereNumber('crewProfileId')
            ->name('staff.crew.show');
        Route::get('/staff/crew/{crewProfileId}/report', [ReportController::class, 'showAssigned'])
            ->whereNumber('crewProfileId')
            ->name('reports.assigned');
        Route::get('/staff/crew/{crewProfileId}/health-data.csv', [ReportController::class, 'exportAssigned'])
            ->whereNumber('crewProfileId')
            ->name('exports.assigned');
    });

    // Astronaut area: every query is scoped to the signed-in user's own crew profile
    Route::middleware(EnsureRole::class.':astronaut')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reports', [ReportController::class, 'showSelf'])->name('reports.self');
        Route::get('/exports/my-health-data.csv', [ReportController::class, 'exportSelf'])->name('exports.self');
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('/alerts/{alertId}', [AlertController::class, 'show'])->whereNumber('alertId')->name('alerts.show');
        Route::post('/alerts/{alertId}', [AlertController::class, 'update'])->whereNumber('alertId')->name('alerts.update');
        Route::get('/check-in', [CheckinController::class, 'create'])->name('checkin.create');
        Route::post('/check-in', [CheckinController::class, 'store'])->name('checkin.store');
        Route::get('/readings', [ReadingController::class, 'index'])->name('readings.index');
        Route::post('/readings', [ReadingController::class, 'store'])->name('readings.store');
    });
});
