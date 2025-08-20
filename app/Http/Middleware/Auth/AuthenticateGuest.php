<?php

namespace App\Http\Middleware\Auth;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthenticateGuest
{
  public function handle($request, Closure $next): RedirectResponse | Response
  {
    if (!Auth::check()) {
      $user = User::create([
        'id' => (int) str_replace('.', '', microtime(true)), // ensure unique ID
        'name' => 'Guest_' . rand(1000, 9999),
        'email' => uniqid('guest_') . '@guest.com',
        'password' => Hash::make(Str::random(10)),
        'is_guest' => true
      ]);

      Auth::login($user);
    }

    return $next($request);
  }
}
