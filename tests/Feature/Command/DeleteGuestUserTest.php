<?php

use App\Models\User;
use App\Services\RoomHelperService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
});

test('Should remove inactive guest from database', function () {
  /** @var Tests\TestCase $this */
  //@ Create inactive guests
  $guest = User::factory()->create([
    'last_active' => Carbon::now()->subHours(6),
    'is_guest' => 1,
  ]);
  $guest2 = User::factory()->create([
    'last_active' => Carbon::now()->subHours(6),
    'is_guest' => 1,
  ]);

  $this->artisan('app:delete-guest-user')
    ->assertSuccessful()
    ->expectsOutput("Inactive guest ($guest->name) has been removed from database")
    ->expectsOutput("Inactive guest ($guest2->name) has been removed from database");

  //@ Database assertions
  assertDatabaseMissing('users', [
    'id' => $guest->id
  ]);
  assertDatabaseMissing('users', [
    'id' => $guest2->id
  ]);
});

test('Should remove inactive guest from database and Redis', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();

  //@ Create inactive guest
  $guest = User::factory()->create([
    'last_active' => Carbon::now()->subHours(6),
    'is_guest' => 1,
    'room_id' => $roomId
  ]);
  $guest2 = User::factory()->create([
    'last_active' => Carbon::now()->subHours(6),
    'is_guest' => 1,
    'room_id' => $roomId
  ]);

  //@ Create room & add guest
  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 3,
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $guest->id, $guest->name);
  $roomHelper->addPlayer($roomId, $guest2->id, $guest2->name);

  $this->artisan('app:delete-guest-user')
    ->assertSuccessful()
    ->expectsOutput("Inactive guest ($guest->name) has been removed from database")
    ->expectsOutput("Inactive guest ($guest2->name) has been removed from database");

  //@ Redis assertions
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertNotContains((string) $guest->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertNotContains((string) $guest2->id, Redis::ZRANGE("room:$roomId:player", 0, -1));

  //@ Database assertions
  assertDatabaseMissing('users', [
    'id' => $guest->id
  ]);
  assertDatabaseMissing('users', [
    'id' => $guest2->id
  ]);
});

test('Should not remove active guest from database and Redis', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();

  //@ Create active guests
  $guest = User::factory()->create([
    'last_active' => Carbon::now(),
    'is_guest' => 1,
    'room_id' => $roomId
  ]);
  $guest2 = User::factory()->create([
    'last_active' => Carbon::now(),
    'is_guest' => 1,
    'room_id' => $roomId
  ]);

  //@ Create room & add guest
  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 3,
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $guest->id, $guest->name);
  $roomHelper->addPlayer($roomId, $guest2->id, $guest2->name);

  $this->artisan('app:delete-guest-user')
    ->assertSuccessful()
    ->doesntExpectOutput();

  //@ Redis assertions
  $this->assertEquals(3, (int) Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertContains((string) $guest->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertContains((string) $guest2->id, Redis::ZRANGE("room:$roomId:player", 0, -1));

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $guest->id
  ]);
  assertDatabaseHas('users', [
    'id' => $guest2->id
  ]);
});
