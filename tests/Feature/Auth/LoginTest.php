<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('Should show login page', function () {
  /** @var \Tests\TestCase $this */
  $this->get(route('auth.show-login'))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('auth/login')
    );
});

test('Should login successfully & save roomId in session', function () {
  /** @var \Tests\TestCase $this */
  User::factory()->create([
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234',
    'room_id' => '123'
  ]);

  $user =  [
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234'
  ];

  $this
    ->post(route('auth.login'), $user)
    ->assertRedirect(route('home'))
    ->assertSessionHas(['roomId']);
});

test('Should not login successfully: all fields missing', function () {
  /** @var \Tests\TestCase $this */
  $this->post(route('auth.login'), [])
    ->assertInvalid(['email', 'password']);
});

test('Should not login successfully: invalid email', function () {
  /** @var \Tests\TestCase $this */
  $user = [
    'email' => 'hongchensee8',
    'password' => '12341234'
  ];

  $this->post(route('auth.login'), $user)
    ->assertInvalid(['email' => 'The email field must be a valid email address.']);
});

test('Should not login successfully: email not exist', function () {
  /** @var \Tests\TestCase $this */
  $user = [
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234'
  ];

  $this->followingRedirects()
    ->post(route('auth.login'), $user)
    ->assertInertia(fn(Assert $page) => $page
      ->whereContains('errors', 'Incorrect credentials'));
});

test('Should not login successfully: password not correct', function () {
  /** @var \Tests\TestCase $this */
  User::factory()->create([
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234'
  ]);

  $user = [
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341235'
  ];

  $this->followingRedirects()
    ->post(route('auth.login'), $user)
    ->assertInertia(fn(Assert $page) => $page
      ->whereContains('errors', 'Incorrect credentials'));
});
