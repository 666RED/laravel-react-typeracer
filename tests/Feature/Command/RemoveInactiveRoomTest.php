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

test('Should remove inactive room from Redis', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $room2Id = (string) Str::uuid();
  $owner = User::factory()->create([
    'room_id' => $roomId
  ]);
  $owner2 = User::factory()->create([
    'room_id' => $room2Id
  ]);

  //@ Create inactive rooms
  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  $room2 = [
    'id' => $room2Id,
    'name' => 'My room 2',
    'owner' => $owner2->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->createRoom($room2Id, $room2, $owner2);
  $expirationTime = Carbon::now()->subHours(2)->timestamp;
  Redis::EXPIREAT("room:$roomId", $expirationTime);
  Redis::EXPIREAT("room:$room2Id", $expirationTime);

  $this->artisan('app:remove-inactive-room')
    ->assertSuccessful()
    ->expectsOutput("Inactive room ($roomId) has been removed")
    ->expectsOutput("Inactive room ($room2Id) has been removed");

  //@ Redis assertions
  $this->assertNotContains($roomId, Redis::SMEMBERS("available-rooms"));
  $this->assertNotContains($room2Id, Redis::SMEMBERS("available-rooms"));
  $this->assertEmpty(Redis::KEYS("room:$roomId*"));
  $this->assertEmpty(Redis::KEYS("room:$room2Id*"));

  //@ Database assertions
  assertDatabaseMissing('users', [
    'id' => $owner->id,
    'room_id' => $roomId
  ]);
  assertDatabaseMissing('users', [
    'id' => $owner2->id,
    'room_id' => $room2Id
  ]);
});

test('Should not remove active room from Redis', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $room2Id = (string) Str::uuid();
  $owner = User::factory()->create([
    'room_id' => $roomId
  ]);
  $owner2 = User::factory()->create([
    'room_id' => $room2Id
  ]);

  //@ Create active rooms
  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  $room2 = [
    'id' => $room2Id,
    'name' => 'My room 2',
    'owner' => $owner2->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->createRoom($room2Id, $room2, $owner2);
  $expirationTime = Carbon::now()->addHours(2)->timestamp;
  Redis::EXPIREAT("room:$roomId", $expirationTime);
  Redis::EXPIREAT("room:$room2Id", $expirationTime);

  $this->artisan('app:remove-inactive-room')
    ->assertSuccessful()
    ->doesntExpectOutput();

  //@ Redis assertions
  $this->assertContains($roomId, Redis::SMEMBERS("available-rooms"));
  $this->assertContains($room2Id, Redis::SMEMBERS("available-rooms"));
  $this->assertNotEmpty(Redis::KEYS("room:$roomId*"));
  $this->assertNotEmpty(Redis::KEYS("room:$room2Id*"));

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => $roomId
  ]);
  assertDatabaseHas('users', [
    'id' => $owner2->id,
    'room_id' => $room2Id
  ]);
});
