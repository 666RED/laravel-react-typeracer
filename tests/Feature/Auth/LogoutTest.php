<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('Should logout successfully', function () {
  /** @var \Tests\TestCase $this */
  $user = User::factory()->create();

  $this->followingRedirects()->actingAs($user)
    ->post(route('logout'))
    ->assertInertia(fn(Assert $page) => $page
      ->component('home/index')
      ->has(
        'flash',
        fn(Assert $page) => $page
          ->where('message', 'You had logout')
          ->where('type', 'success')
      ));
});
