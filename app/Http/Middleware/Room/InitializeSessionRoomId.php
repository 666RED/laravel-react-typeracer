<?php

namespace App\Http\Middleware\Room;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class InitializeSessionRoomId
{
  public function handle(Request $request, Closure $next): Response
  {
    if (session('roomId')) {
      return $next($request);
    }

    if (Auth::check()) {
      $roomId = Auth::user()->room_id;
      if ($roomId) {
        if (Redis::SISMEMBER("available-rooms", $roomId) && Redis::EXISTS("room:$roomId")) {
          session()->put('roomId', $roomId);
        }
      }
    }

    return $next($request);
  }
}
