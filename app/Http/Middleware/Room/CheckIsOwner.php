<?php

namespace App\Http\Middleware\Room;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CheckIsOwner
{
  public function handle(Request $request, Closure $next): Response
  {
    $roomId = session('roomId');
    $userId = Auth::id();

    $ownerId = (int) Redis::HGET("room:$roomId", 'owner');

    if ($userId !== $ownerId) {
      return back()->with(['message' => 'Only room owner can perform this action', 'type' => 'warning']);
    }

    return $next($request);
  }
}
