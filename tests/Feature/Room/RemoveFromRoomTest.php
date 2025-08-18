<?php

use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseHas;

test('Should remove player from the room', function () {
  /** @var Tests\TestCase $this */
  $roomId = Str::uuid();
  $message = 'Removed from room';

  $user = User::factory()->create([
    'room_id' => $roomId
  ]);
  session()->put('roomId', $roomId);

  $this->actingAs($user)->followingRedirects()
    ->post(route('room.remove'), ['message' => $message])
    ->assertSessionMissing('roomId')
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('home/index')
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', $message)
            ->where('type', 'warning')
        )
    );

  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});
