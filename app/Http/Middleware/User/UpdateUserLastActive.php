<?php

namespace App\Http\Middleware\User;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastActive
{
  public function handle(Request $request, Closure $next): Response
  {
    if (Auth::check()) {
      User::where('id', Auth::id())->update(['last_active' => Carbon::now()]);
    }

    return $next($request);
  }
}
