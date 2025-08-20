<?php

namespace App\Http\Middleware\Room;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class UpdateRoomExpirationTime
{
  public function handle(Request $request, Closure $next): Response
  {
    $roomId = session('roomId');
    $isRoomExist = Redis::SISMEMBER("available-rooms", $roomId) && Redis::EXISTS("room:$roomId");

    if ($roomId && $isRoomExist) {
      $expirationTime = Carbon::now()->addHours(2)->timestamp;
      Redis::EXPIREAT("room:$roomId", $expirationTime);
    }

    return $next($request);
  }
}
