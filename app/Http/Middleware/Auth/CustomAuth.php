<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

class CustomAuth extends Authenticate
{
  public function __construct(AuthManager $auth)
  {
    parent::__construct($auth);
  }

  protected function redirectTo(Request $request)
  {
    return route('auth.show-login');
  }
}
