<?php

namespace App\Http\Middleware\Race;

use App\Helpers\RaceHelper;
use App\Services\RaceHelperService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class AbortRace
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

    $path = $request->getRequestUri();

    //@ refresh page
    if (str_contains($path, '/race')) {
      return $next($request);
    }


    if (Redis::EXISTS("room:$roomId:race") && Redis::SISMEMBER("room:$roomId:race:inRacePlayer", $user->id)) {
      $raceHelper = app(RaceHelperService::class);
      $raceHelper->abortRace($roomId, $user);
    }

    return $next($request);
  }
}
