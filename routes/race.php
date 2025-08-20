<?php

use App\Http\Controllers\Room\RaceController;
use App\Http\Middleware\Auth\CustomAuth;
use App\Http\Middleware\Room\CheckIsOwner;
use Illuminate\Support\Facades\Route;

Route::prefix('race')->middleware([CustomAuth::class])->group(function () {
  Route::middleware(CheckIsOwner::class)->group(function () {
    Route::post('/race-ready', [RaceController::class, 'raceReady'])->name('race.race-ready');
  });

  Route::post('/update-progress', [RaceController::class, 'updateProgress'])->name('race.update-progress');
  Route::post('/save-result', [RaceController::class, 'saveResult'])->name('race.save');
  Route::post('/save-not-complete-result', [RaceController::class, 'saveNotCompleteResult'])->name('race.save-not-complete');
  Route::post('/toggle-ready-state', [RaceController::class, 'toggleReadyState'])->name('race.toggle-ready-state');
});
