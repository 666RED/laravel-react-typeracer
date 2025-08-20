<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('Auth user should not access protected routes', function () {
  /** @var \Tests\TestCase $this */
  $user = User::factory()->create();

  $protectedRoutes = [
    ['name' => 'auth.show-register', 'method' => 'get'],
    ['name' => 'auth.show-login', 'method' => 'get'],
    ['name' => 'auth.register', 'method' => 'post'],
    ['name' => 'auth.login', 'method' => 'post'],
    ['name' => 'auth.remove-session', 'method' => 'post'],
  ];

  foreach ($protectedRoutes as $route) {
    $response = $route['method'] === 'get'
      ? $this->followingRedirects()->actingAs($user)->get(route($route['name']))
      : $this->followingRedirects()->actingAs($user)->post(route($route['name']));

    $response->assertInertia(fn(Assert $page) => $page
      ->has('flash', fn(Assert $page) => $page
        ->where(
          'message',
          'You are not allowed to perform this action'
        )->where('type', 'warning')));
  }
});

test('Unauthorize user should not access logout route', function () {
  /** @var \Tests\TestCase $this */
  $this->followingRedirects()
    ->post(route('logout'))
    ->assertInertia(fn(Assert $page) => $page
      ->component('auth/login'));
});
