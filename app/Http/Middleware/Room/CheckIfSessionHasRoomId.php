<?php

namespace App\Http\Middleware\Room;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSessionHasRoomId
{
  public function handle(Request $request, Closure $next): Response
  {
    $roomId = session('roomId');

    // ? Check if roomId is stored in session
    if (!$roomId) {
      return to_route('home')->with(['message' => 'Join a room first', 'type' => 'warning']);
    }

    return $next($request);
  }
}