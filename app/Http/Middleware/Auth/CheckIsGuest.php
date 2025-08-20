<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIsGuest
{
  public function handle(Request $request, Closure $next): Response
  {
    //@ If user has logged in / user is logged in as guest
    if (Auth::check() && !Auth::user()->is_guest) {
      return back()->with(['message' => 'You are not allowed to perform this action', 'type' => 'warning']);
    }

    return $next($request);
  }
}
