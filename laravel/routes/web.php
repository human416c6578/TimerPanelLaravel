<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MapImageController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerVersusController;
use App\Http\Controllers\ReplayController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\TimeController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/{uuid}', [PlayerController::class, 'profile'])->name('players.show');
Route::get('/players/{a}/vs/{b}', [PlayerVersusController::class, 'show'])->name('players.versus');
Route::delete('/players/{uuid}/times', [PlayerController::class, 'deleteUserRankedTimes'])
    ->name('players.delete.times');

Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
Route::get('/maps/{uuid}', [MapController::class, 'show'])->name('maps.show');
Route::get('/map-images/{name}', [MapImageController::class, 'show'])->middleware('throttle:120,1')->name('maps.image');
Route::delete('/maps/{uuid}/time', [MapController::class, 'deleteMapRankedTime'])
    ->name('maps.delete.time');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::get('/proxy', [App\Http\Controllers\ProxyController::class, 'fetch']);
Route::get('/replays', [ReplayController::class, 'index'])->name('replays.index');

// A single run: stats, standing, and the replay when it is the record.
Route::get('/runs/{map}/{category}/{user}', [RunController::class, 'show'])->name('runs.show');

// The old replay URL pointed at whoever held the record; keep it working.
Route::get('/replays/{map}/{category}', [ReplayController::class, 'show'])->name('replays.show');

Route::get('/', [TimeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified'])
        ->name('dashboard');

    Route::post('dashboard/leaderboards/refresh', [DashboardController::class, 'refreshLeaderboards'])
        ->middleware(['verified'])
        ->name('dashboard.leaderboards.refresh');

    Route::post('dashboard/cache/clear', [DashboardController::class, 'clearCaches'])
        ->middleware(['verified'])
        ->name('dashboard.cache.clear');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
