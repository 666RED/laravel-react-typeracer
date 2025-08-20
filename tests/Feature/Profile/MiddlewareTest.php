<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;


test('Unauthorized user should not access protected routes', function () {
  $protectedRoutes = [
    ['name' => 'profile.update', 'method' => 'post'],
    ['name' => 'profile.destroy', 'method' => 'delete'],
  ];

  foreach ($protectedRoutes as $route) {
    $response = $route['method'] === 'get'
      ? $this->followingRedirects()->get(route($route['name'], ['userId' => 1]))
      : ($route['method'] === 'post'
        ? $this->followingRedirects()->post(route($route['name']))
        : $this->followingRedirects()->delete(route($route['name'])));

    $response->assertInertia(fn(Assert $page) => $page
      ->component('auth/login'));
  }
});
