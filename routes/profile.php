<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\Auth\CustomAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->group(function () {
  Route::middleware(CustomAuth::class)->group(function () {
    Route::post('/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/destroy', [ProfileController::class, 'destroy'])->name("profile.destroy");
  });

  Route::get('/{userId}', [ProfileController::class, 'show'])->name('profile.show');

  Route::get('/{userId}/results', [ProfileController::class, 'showResults'])->name('profile.show-results');
});
