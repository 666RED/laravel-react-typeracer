<?php

use Inertia\Testing\AssertableInertia as Assert;

test('Unauthorized user should not send room message', function () {
  /** @var Tests\TestCase $this */
  $this->followingRedirects()
    ->post(route('message.send-message'))
    ->assertInertia(fn(Assert $page) => $page
      ->component('auth/login'));
});
