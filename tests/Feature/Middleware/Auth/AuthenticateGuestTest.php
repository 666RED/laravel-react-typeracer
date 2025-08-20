<?php

use App\Http\Middleware\Auth\AuthenticateGuest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

test('Should log in as guest user', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('room.create'));

  $middleware = new AuthenticateGuest();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertTrue(Auth::check());
  $this->assertTrue(Auth::user()->is_guest);
  $this->assertEquals('Next called', $response->getContent());
});

test('Should not log in as guest user', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('room.create'));
  $user = User::factory()->create();
  Auth::login($user);

  $middleware = new AuthenticateGuest();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertTrue(Auth::check());
  $this->assertFalse(Auth::user()->is_guest);
  $this->assertEquals('Next called', $response->getContent());
});
