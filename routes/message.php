<?php

use App\Http\Controllers\MessageController;
use App\Http\Middleware\Auth\CustomAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('message')->middleware([CustomAuth::class])->group(function () {
  Route::post('/send-message', [MessageController::class, 'sendMessage'])->name('message.send-message');
});
