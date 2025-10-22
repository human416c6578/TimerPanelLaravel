<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TimeController;
use App\Http\Controllers\ReplayController;


Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/{uuid}', [PlayerController::class, 'profile'])->name('players.show');
Route::delete('/players/{uuid}/times', [PlayerController::class, 'deleteUserRankedTimes'])
    ->name('players.delete.times');


Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
Route::get('/maps/{uuid}', [MapController::class, 'show'])->name('maps.show');
Route::delete('/maps/{uuid}/time', [MapController::class, 'deleteMapRankedTime'])
    ->name('maps.delete.time');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::get('/proxy', [App\Http\Controllers\ProxyController::class, 'fetch']);
Route::get('/replays/{map}/{category}', [ReplayController::class, 'show'])->name('replays.show');

Route::get('/', [TimeController::class, 'index'])->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
