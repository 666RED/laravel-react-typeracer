<?php

use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('Should show register page', function () {
  /** @var \Tests\TestCase $this */
  $this->get(route('auth.show-register'))
    ->assertInertia(fn(Assert $page) => $page->component('auth/register'));
});

test('Should register successfully', function () {
  /** @var \Tests\TestCase $this */
  $newUser = [
    'name' => 'See Hong Chen',
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234',
    'password_confirmation' => '12341234'
  ];

  $this->followingRedirects()
    ->post(route('auth.register'), $newUser)
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('home/index')
        ->has(
          'flash',
          fn(Assert $page) => $page
            ->where('message', 'Registered successfully')
            ->where('type', 'success')
        )
    );

  assertDatabaseHas('users', ['email' => $newUser['email']]);
});

test('Should not register successfully: missing all fields', function () {
  /** @var \Tests\TestCase $this */
  $userCount = User::count();

  $this->post(route('auth.register'), [])
    ->assertInvalid(['email', 'name', 'password']);

  assertDatabaseCount('users', $userCount);
});

test('Should not register successfully: name > 255', function () {
  /** @var \Tests\TestCase $this */
  $userCount = User::count();

  $newUser = [
    'name' => Str::random(256),
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234',
    'password_confirmation' => '12341234'
  ];

  $this->post(route('auth.register'), $newUser)
    ->assertInvalid(['name' => 'The name field must not be greater than 255 characters.']);

  assertDatabaseCount('users', $userCount);
});

test('Should not register successfully: email existed', function () {
  /** @var \Tests\TestCase $this */
  User::factory()->create([
    'email' => 'hongchensee8@gmail.com'
  ]);

  $newUser = [
    'name' => 'See Hong Chen',
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234',
    'password_confirmation' => '12341234'
  ];

  $userCount = User::count();

  $this->post(route('auth.register'), $newUser)
    ->assertInvalid(['email' => 'The email has already been taken.']);

  assertDatabaseCount('users', $userCount);
});

test('Should not register successfully: invalid email', function () {
  /** @var \Tests\TestCase $this */
  $userCount = User::count();

  $newUser = [
    'name' => 'See Hong Chen',
    'email' => 'hongchensee8',
    'password' => '12341234',
    'password_confirmation' => '12341234'
  ];

  $this->post(route('auth.register'), $newUser)
    ->assertInvalid(['email' => 'The email field must be a valid email address.']);

  assertDatabaseCount('users', $userCount);
});

test('Should not register successfully: password < 8', function () {
  /** @var \Tests\TestCase $this */
  $userCount = User::count();

  $newUser = [
    'name' => 'See Hong Chen',
    'email' => 'hongchensee8@gmail.com',
    'password' => '1234123',
    'password_confirmation' => '1234123'
  ];

  $this->post(route('auth.register'), $newUser)
    ->assertInvalid(['password' => 'The password field must be at least 8 characters.']);

  assertDatabaseCount('users', $userCount);
});

test('Should not register successfully: password != password_confirmation', function () {
  /** @var \Tests\TestCase $this */
  $userCount = User::count();

  $newUser = [
    'name' => 'See Hong Chen',
    'email' => 'hongchensee8@gmail.com',
    'password' => '12341234',
    'password_confirmation' => '12341235'
  ];

  $this->post(route('auth.register'), $newUser)
    ->assertInvalid(['password' => 'The password field confirmation does not match.']);

  assertDatabaseCount('users', $userCount);
});