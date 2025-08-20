<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\Auth\CheckIsGuest;
use App\Http\Middleware\Auth\CustomAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(CheckIsGuest::class)->group(function () {
  Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.show-register');
  Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

  Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.show-login');
  Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

  Route::post('/remove-session-room-id', [AuthController::class, 'removeSessionRoomId'])->name('auth.remove-session');
});

Route::middleware(CustomAuth::class)->post('/logout', [AuthController::class, 'logout'])->name('logout');
