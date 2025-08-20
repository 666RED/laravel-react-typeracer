<?php

namespace App\Http\Middleware\Room;

use App\Models\User;
use App\Services\RoomHelperService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CheckIfRoomExist
{
  public function handle(Request $request, Closure $next): Response
  {
    $routeRoomId = $request->route('roomId');
    $sessionRoomId = session('roomId');

    //@ Check if current room still existed in Redis
    $isRoomExist = $routeRoomId ? Redis::SISMEMBER("available-rooms", $routeRoomId) : Redis::SISMEMBER("available-rooms", $sessionRoomId);
    $isRoomHashExist = $routeRoomId ? Redis::EXISTS("room:$routeRoomId") : Redis::EXISTS("room:$sessionRoomId");

    //@ if room set has room but room hash has expired -> remove all room fields
    if (!$isRoomExist || !$isRoomHashExist) {
      //@ If user join the expired room -> remove the room
      if (!$routeRoomId && $sessionRoomId) {
        $helper = app(RoomHelperService::class);
        $helper->removeRoom($sessionRoomId);

        session()->remove('roomId');
        $user = User::find(Auth::id());

        if ($user->room_id) {
          $user->room_id = null;
          $user->save();
        }
      }

      return to_route('home')->with(['message' => 'Room not found', 'type' => 'warning']);
    }

    return $next($request);
  }
}
