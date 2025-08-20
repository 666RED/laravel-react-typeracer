<?php

use App\Events\Message\MessageSent;
use App\Events\Room\NewRoomCreated;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  Redis::flushDb();
});

afterEach(function () {
  Redis::flushDb();
});

test('Should create public room', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $newRoom = [
    'name' => 'My room',
    'roomId' => (string) Str::uuid(),
    'playerCount' => '2',
    'private' => false
  ];

  $roomId = $newRoom['roomId'];

  $this->actingAs($user)
    ->post(route('room.create'), $newRoom)
    ->assertSessionHas("roomId", $newRoom['roomId'])
    ->assertRedirect(route('room.show', ['roomId' => $roomId]));

  $expectedRoom = [
    'id' => $roomId,
    'name' => $newRoom['name'],
    'owner' => (string) $user->id,
    'playerCount' => 1,
    'maxPlayer' => $newRoom['playerCount'],
    'private' => '0'
  ];

  $expectedPlayer = [
    'id' => (string) $user->id,
    'name' => $user->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  $expectedMessage = [
    'id' => Redis::GET("room:$roomId:message:id"),
    'senderId' => (string) $user->id,
    'senderName' => $user->name,
    'text' => "$user->name created the room",
    'isNotification' => true
  ];

  //@ Broadcast event assertions
  Event::assertDispatched(
    MessageSent::class,
    1,
  );
  Event::assertDispatched(
    NewRoomCreated::class,
    1,
  );

  //@ Redis assertions
  $this->assertEquals($expectedRoom, Redis::HGETALL("room:$roomId"));
  $this->assertContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertEquals($expectedPlayer, Redis::HGETALL("room:$roomId:player:$user->id"));
  $this->assertContains($roomId, Redis::SMEMBERS('available-rooms'));
  $this->assertEquals($expectedMessage, json_decode(Redis::RPOP("room:$roomId:message"), 1));

  //@ Database assertions
  assertDatabaseHas('users', ['id' => $user->id, 'room_id' => $roomId]);
});

test('Should create private room', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $newRoom = [
    'name' => 'My private room',
    'roomId' => (string) Str::uuid(),
    'playerCount' => 2,
    'private' => true
  ];

  $roomId = $newRoom['roomId'];

  $this->actingAs($user)
    ->post(route('room.create'), $newRoom)
    ->assertSessionHas("roomId", $newRoom['roomId'])
    ->assertRedirect(route('room.show', ['roomId' => $roomId]));

  $expectedRoom = [
    'id' => $roomId,
    'name' => $newRoom['name'],
    'owner' => (string) $user->id,
    'playerCount' => 1,
    'maxPlayer' => $newRoom['playerCount'],
    'private' => '1'
  ];

  $expectedPlayer = [
    'id' => (string) $user->id,
    'name' => $user->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  $expectedMessage = [
    'id' => Redis::GET("room:$roomId:message:id"),
    'senderId' => (string) $user->id,
    'senderName' => $user->name,
    'text' => "$user->name created the room",
    'isNotification' => true
  ];

  //@ Broadcast event assertions
  Event::assertDispatched(
    MessageSent::class,
    1,
  );
  Event::assertNotDispatched(
    NewRoomCreated::class,
  );

  //@ Redis assertions
  $this->assertEquals($expectedRoom, Redis::HGETALL("room:$roomId"));
  $this->assertContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertEquals($expectedPlayer, Redis::HGETALL("room:$roomId:player:$user->id"));
  $this->assertContains($roomId, Redis::SMEMBERS('available-rooms'));
  $this->assertEquals($expectedMessage, json_decode(Redis::RPOP("room:$roomId:message"), 1));

  //@ Database assertions
  assertDatabaseHas('users', ['id' => $user->id, 'room_id' => $roomId]);
});

//@ Validation error
test('Should not create room: all fields missing', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $this->actingAs($user)->post(route('room.create'))
    ->assertInvalid(['name', 'roomId', 'playerCount', 'private']);
});

test('Should not create room: name > 50', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $newRoom = [
    'name' => Str::random(51),
    'roomId' => (string) Str::uuid(),
    'playerCount' => 2,
    'private' => false
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid(['name' => 'The name field must not be greater than 50 characters.']);
});

test('Should not create room: invalid roomId', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $newRoom = [
    'name' => 'My Room',
    'roomId' => 'invalid roomId',
    'playerCount' => 2,
    'private' => false
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.']);
});

test('Should not create room: playerCount < 2', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $newRoom = [
    'name' => 'My Room',
    'roomId' => (string) Str::uuid(),
    'playerCount' => 1,
    'private' => false
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid(['playerCount' => "The player count field must be between 2 and 5."]);
});

test('Should not create room: playerCount > 5', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $newRoom = [
    'name' => 'My Room',
    'roomId' => (string) Str::uuid(),
    'playerCount' => 6,
    'private' => false
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid(['playerCount' => "The player count field must be between 2 and 5."]);
});

test('Should not create room: invalid private', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $newRoom = [
    'name' => 'My Room',
    'roomId' => (string) Str::uuid(),
    'playerCount' => 2,
    'private' => 'false' //@ should be boolean
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid(['private' => 'The private field must be true or false.']);
});

test('Should not modify Redis', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $newRoom = [
    'roomId' => (string) Str::uuid(),
  ];

  $roomId = $newRoom['roomId'];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid([
      'name',
      'playerCount',
      'private'
    ]);

  //@ Redis assertions
  $this->assertEmpty(Redis::HGETALL("room:$roomId"));
  $this->assertEmpty(Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertEmpty(Redis::HGETALL("room:$roomId:player:$user->id"));
  $this->assertEmpty(Redis::SMEMBERS('available-rooms'));
  $this->assertEmpty(Redis::RPOP("room:$roomId:message"));
});

test('Should not dispatch events', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $this->actingAs($user)->post(route('room.create'))
    ->assertInvalid([
      'name',
      'roomId',
      'playerCount',
      'private'
    ]);

  //@ Broadcast event assertions
  Event::assertNotDispatched(
    MessageSent::class,
  );
  Event::assertNotDispatched(
    NewRoomCreated::class,
  );
});

test('Should not modify user room_id column', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $newRoom = [
    'roomId' => (string) Str::uuid(),
  ];

  $this->actingAs($user)->post(route('room.create'), $newRoom)
    ->assertInvalid([
      'name',
      'playerCount',
      'private'
    ]);

  //@ Database assertions
  assertDatabaseHas('users', ['id' => $user->id, 'room_id' => null]);
});
