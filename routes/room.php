<?php

use App\Http\Controllers\Room\RaceController;
use App\Http\Controllers\Room\RoomController;
use App\Http\Middleware\Auth\AuthenticateGuest;
use App\Http\Middleware\Auth\CustomAuth;
use App\Http\Middleware\Room\CheckIfPlayerInTheRoom;
use App\Http\Middleware\Room\CheckIfRoomExist;
use App\Http\Middleware\Room\CheckIfSessionHasRoomId;
use App\Http\Middleware\Room\CheckIsOwner;
use App\Http\Middleware\Room\UpdateRoomExpirationTime;
use Illuminate\Support\Facades\Route;

Route::prefix('room')->middleware(UpdateRoomExpirationTime::class)->group(function () {
  Route::middleware([CheckIfSessionHasRoomId::class])->group(function () {
    Route::post('/remove-from-room', [RoomController::class, 'removeFromRoom'])->name('room.remove');

    Route::middleware([CheckIfRoomExist::class, CheckIfPlayerInTheRoom::class])->group(function () {
      Route::get('/{roomId}', [RoomController::class, 'showRoom'])->name('room.show');
      Route::get('/{roomId}/race', [RoomController::class, 'showRace'])->name('room.show-race');
      Route::get('/{roomId}/spectate', [RoomController::class, 'spectateRace'])->name('room.spectate-race');

      Route::post('/leave-room', [RoomController::class, 'leaveRoom'])->name('room.leave');
      Route::post('/leave-and-join', [RoomController::class, 'leavePreviousRoomAndJoinNewRoom'])->name('room.leave-and-join');

      Route::middleware(CheckIsOwner::class)->group(function () {
        Route::delete('/delete-room', [RoomController::class, 'deleteRoom'])->name('room.delete');
        Route::post('/transfer-and-join', [RoomController::class, 'transferOwnershipAndJoinNewRoom'])->name('room.transfer-and-join');
        Route::post('/transfer-and-leave', [RoomController::class, 'transferOwnershipAndLeave'])->name('room.transfer-and-leave');
        Route::patch('/update-room-setting', [roomController::class, 'updateRoomSetting'])->name('room.update');
      });
    });
  });

  Route::middleware(AuthenticateGuest::class)->group(function () {
    Route::post('/join-room', [RoomController::class, 'joinRoom'])->name('room.join');
    Route::post('/create-room', [RoomController::class, 'createRoom'])->name('room.create');
  });
});
