<?php

use Inertia\Testing\AssertableInertia as Assert;


test('Should remove roomId session & redirect to home', function () {
  /** @var \Tests\TestCase $this */
  session()->put('roomId', '123');

  $this->followingRedirects()
    ->post(route('auth.remove-session'))
    ->assertSessionMissing('roomId')
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('home/index')
        ->has(
          'flash',
          fn(Assert $page) => $page

            ->where('message', 'You have idle for more than 6 hours')
            ->where('type', 'warning')
        )
    );
});
