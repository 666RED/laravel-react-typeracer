<?php

use Inertia\Testing\AssertableInertia as Assert;

test('Should redirect unauthorized user to login page', function () {
  /** @var Tests\TestCase $this */
  $protectedRoute = route('logout');
  $response = $this->post($protectedRoute);

  $response->assertRedirect(route('auth.show-login'));
});

test('Should not redirect unauthorized user to login page', function () {
  /** @var Tests\TestCase $this */
  $notProtectedRoute = route('auth.show-register');

  $this->get($notProtectedRoute)->assertInertia(
    fn(Assert $page) =>
    $page->component('auth/register')
  );
});
