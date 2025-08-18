<?php

use App\Http\Middleware\Auth\CheckIsGuest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

test('Guest user should pass the middleware', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('auth.show-register'));

  $middleware = new CheckIsGuest();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  $this->assertEquals('Next called', $response->getContent());
});

test('Auth user should not pass the middleware', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('auth.show-register'));
  $user = User::factory()->create();
  Auth::login($user);

  $middleware = new CheckIsGuest();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  $this->assertEquals('You are not allowed to perform this action', session('message'));
  $this->assertEquals('warning', session('type'));
});
