<?php

namespace App\Http\Middleware\Race;

use App\Events\Race\ToggleReadyState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CancelRaceReady
{
  public function handle(Request $request, Closure $next): Response
  {
    if (!$request->isMethod('GET')) {
      return $next($request);
    }

    $roomId = session('roomId');
    $user = Auth::user();

    if (!$roomId || !$user) {
      return $next($request);
    }

    $isRaceStarted = Redis::EXISTS("room:$roomId:race");

    //@ If there is a race in progress, do not need to toggle the state
    if ($isRaceStarted) {
      return $next($request);
    }

    if (
      //@ Toggle player ready state to false when navigating to other page with state of ready
      url()->current() !== url()->previous() &&
      preg_match(
        '#/room/[^/]+$#',
        url()->previous()
      )
      &&
      Redis::SISMEMBER("room:$roomId:race:player", $user->id)

    ) {
      Redis::SREM("room:$roomId:race:player", $user->id);
      broadcast(new ToggleReadyState($roomId, $user->id, false));
    }

    return $next($request);
  }
}
