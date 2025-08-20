<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  $availableRoomsFn = fn() => collect(
    Redis::pipeline(function ($pipe) {
      $roomsKeys = Redis::SMEMBERS('available-rooms');
      foreach ($roomsKeys as $key) {
        $pipe->HGETALL("room:$key");
      }
    })
  )->filter(
    fn($room) => isset($room['private']) && $room['private'] === '0'
  )->values();

  return Inertia::render('home/index', ['availableRooms' => $availableRoomsFn]);
})->name('home');

require __DIR__ . '/room.php';
require __DIR__ . '/race.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/message.php';
require __DIR__ . '/profile.php';