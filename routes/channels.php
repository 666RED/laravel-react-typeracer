<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('public-rooms', function (User $user = null, $id) {
  return true;
});

Broadcast::channel("room.{roomId}", function (User $user, $roomId) {
  return $user->room_id === $roomId;
});

Broadcast::channel('user.{userId}', function (User $user) {
  return Auth::id() === $user->id;
});
