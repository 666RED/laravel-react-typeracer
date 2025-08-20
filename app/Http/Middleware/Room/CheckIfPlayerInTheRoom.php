<?php

namespace App\Http\Middleware\Room;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CheckIfPlayerInTheRoom
{
  public function handle(Request $request, Closure $next): Response
  {
    $roomId = session('roomId');
    $userId = Auth::id();

    if (Redis::ZSCORE("room:$roomId:player", $userId) === null) {
      return to_route('home')->with(['message' => 'Join a room first', 'type' => 'warning']);
    }

    return $next($request);
  }
}
