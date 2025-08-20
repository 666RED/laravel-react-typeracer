<?php

use App\Events\Message\MessageSent;
use App\Events\Room\NewRoomCreated;
use App\Events\Room\RemoveRoomInLobby;
use App\Events\Room\UpdateRoomInLobby;
use App\Events\Room\UpdateRoomSetting;
use App\Models\User;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
  Redis::flushDb();
  //@ Create room setup
  $owner = User::factory()->create();
  $roomId = (string) Str::uuid();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => 0
  ];

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $owner);
  $owner->saveRoomId($roomId);


  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('ownerId');
});

test('Should update all room settings', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Add new player (for transfer ownership)
  $newPlayer = User::factory()->create();
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $newPlayer->id, $newPlayer->name);
  $newPlayer->saveRoomId($roomId);

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 3,
    'owner' => $newPlayer->id,
    'private' => true
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Room setting updated')
            ->where('type', 'success')
        )
    );

  //@ Event assertions
  Event::assertDispatched(MessageSent::class, 1);
  Event::assertDispatched(UpdateRoomInLobby::class, 1);
  Event::assertDispatched(RemoveRoomInLobby::class, 1);
  Event::assertNotDispatched(NewRoomCreated::class);
  Event::assertDispatched(UpdateRoomSetting::class, 1);

  //@ Redis assertions
  $updatedRoom = Redis::HGETALL("room:$roomId");
  $expectedRoom = [
    'id' => $roomId,
    'name' => $roomSetting['name'],
    'owner' => (string) $roomSetting['owner'],
    'playerCount' => $updatedRoom['playerCount'],
    'maxPlayer' => $roomSetting['maxPlayer'],
    'private' => '1'
  ];

  $this->assertEquals($expectedRoom, $updatedRoom);
});

test('Should update room name only', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 2,
    'owner' => $owner->id,
    'private' => false
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Room setting updated')
            ->where('type', 'success')
        )
    );

  //@ Event assertions
  Event::assertDispatched(UpdateRoomSetting::class, 1);
  Event::assertDispatched(UpdateRoomInLobby::class, 1);
  Event::assertNotDispatched(MessageSent::class);
  Event::assertNotDispatched(RemoveRoomInLobby::class);
  Event::assertNotDispatched(NewRoomCreated::class);

  //@ Redis assertions
  $updatedRoom = Redis::HGETALL("room:$roomId");
  $expectedRoom = [
    'id' => $roomId,
    'name' => $roomSetting['name'],
    'owner' => (string) $roomSetting['owner'],
    'playerCount' => $updatedRoom['playerCount'],
    'maxPlayer' => $roomSetting['maxPlayer'],
    'private' => '0'
  ];

  $this->assertEquals($expectedRoom, $updatedRoom);
});

test('Should update room maxPlayer only', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  $roomSetting = [
    'name' => 'My room',
    'maxPlayer' => 3,
    'owner' => $owner->id,
    'private' => false
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Room setting updated')
            ->where('type', 'success')
        )
    );

  //@ Event assertions
  Event::assertDispatched(UpdateRoomSetting::class, 1);
  Event::assertDispatched(UpdateRoomInLobby::class, 1);
  Event::assertNotDispatched(MessageSent::class);
  Event::assertNotDispatched(RemoveRoomInLobby::class);
  Event::assertNotDispatched(NewRoomCreated::class);

  //@ Redis assertions
  $updatedRoom = Redis::HGETALL("room:$roomId");
  $expectedRoom = [
    'id' => $roomId,
    'name' => $roomSetting['name'],
    'owner' => (string) $roomSetting['owner'],
    'playerCount' => $updatedRoom['playerCount'],
    'maxPlayer' => $roomSetting['maxPlayer'],
    'private' => '0'
  ];

  $this->assertEquals($expectedRoom, $updatedRoom);
});

test('Should transfer room ownership only', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Add new player (for transfer ownership)
  $newPlayer = User::factory()->create();
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $newPlayer->id, $newPlayer->name);
  $newPlayer->saveRoomId($roomId);

  $roomSetting = [
    'name' => 'My room',
    'maxPlayer' => 2,
    'owner' => $newPlayer->id,
    'private' => false
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Room setting updated')
            ->where('type', 'success')
        )
    );

  //@ Event assertions
  Event::assertDispatched(UpdateRoomSetting::class, 1);
  Event::assertDispatched(MessageSent::class, 1);
  Event::assertNotDispatched(UpdateRoomInLobby::class);
  Event::assertNotDispatched(RemoveRoomInLobby::class);
  Event::assertNotDispatched(NewRoomCreated::class);

  //@ Redis assertions
  $updatedRoom = Redis::HGETALL("room:$roomId");
  $expectedRoom = [
    'id' => $roomId,
    'name' => $roomSetting['name'],
    'owner' => (string) $roomSetting['owner'],
    'playerCount' => $updatedRoom['playerCount'],
    'maxPlayer' => $roomSetting['maxPlayer'],
    'private' => '0'
  ];

  $this->assertEquals($expectedRoom, $updatedRoom);
});

test('Should update room private setting only', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Change private setting to true & change it back (for dispatching NewRoomCreated event)
  Redis::HSET("room:$roomId", 'private', '1');

  $roomSetting = [
    'name' => 'My room',
    'maxPlayer' => 2,
    'owner' => $owner->id,
    'private' => false
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Room setting updated')
            ->where('type', 'success')
        )
    );

  //@ Event assertions
  Event::assertDispatched(UpdateRoomSetting::class, 1);
  Event::assertDispatched(NewRoomCreated::class, 1);
  Event::assertNotDispatched(RemoveRoomInLobby::class);
  Event::assertNotDispatched(MessageSent::class);
  Event::assertNotDispatched(UpdateRoomInLobby::class);

  //@ Redis assertions
  $updatedRoom = Redis::HGETALL("room:$roomId");
  $expectedRoom = [
    'id' => $roomId,
    'name' => $roomSetting['name'],
    'owner' => (string) $roomSetting['owner'],
    'playerCount' => $updatedRoom['playerCount'],
    'maxPlayer' => $roomSetting['maxPlayer'],
    'private' => '0'
  ];

  $this->assertEquals($expectedRoom, $updatedRoom);
});

test('Should not update room settings: maxPlayer < playerCount', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Update room max player
  Redis::HSET("room:$roomId", 'maxPlayer', 3);

  //@ Add 2 new players
  $helper = app(RoomHelperService::class);

  $newPlayer = User::factory()->create();
  $newPlayer2 = User::factory()->create();
  $helper->addPlayer($roomId, $newPlayer->id, $newPlayer->name);
  $helper->addPlayer($roomId, $newPlayer2->id, $newPlayer2->name);
  $newPlayer->saveRoomId($roomId);
  $newPlayer2->saveRoomId($roomId);

  $roomSetting = [
    'name' => 'My room',
    'maxPlayer' => 2, //@ lower than playerCount
    'owner' => $owner->id,
    'private' => false
  ];

  $this->actingAs($owner)->followingRedirects()
    ->patch(route('room.update'), $roomSetting)
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', '# Players should not less than number of players in the room')
    );
});

//@ Validation errors
test('Should not update room settings: missing all fields', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $this->actingAs($owner)
    ->patch(route('room.update'))
    ->assertInvalid(['name', 'maxPlayer', 'owner', 'private']);
});

test('Should not update room settings: name > 50', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $roomSetting = [
    'name' => Str::random(51),
    'maxPlayer' => 3,
    'owner' => $owner->id,
    'private' => true
  ];

  $this->actingAs($owner)
    ->patch(route('room.update'), $roomSetting)
    ->assertInvalid(['name' => "The name field must not be greater than 50 characters."]);
});

test('Should not update room settings: maxPlayer < 2', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 1,
    'owner' => $owner->id,
    'private' => true
  ];

  $this->actingAs($owner)
    ->patch(route('room.update'), $roomSetting)
    ->assertInvalid(['maxPlayer' => "The max player field must be between 2 and 5."]);
});

test('Should not update room settings: maxPlayer > 5', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 6,
    'owner' => $owner->id,
    'private' => true
  ];

  $this->actingAs($owner)
    ->patch(route('room.update'), $roomSetting)
    ->assertInvalid(['maxPlayer' => "The max player field must be between 2 and 5."]);
});

test('Should not update room settings: invalid owner id', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 2,
    'owner' => -1,
    'private' => true
  ];

  $this->actingAs($owner)
    ->patch(route('room.update'), $roomSetting)
    ->assertInvalid(['owner' => "The selected owner is invalid."]);
});

test('Should not update room settings: invalid private', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $roomSetting = [
    'name' => 'Update room name',
    'maxPlayer' => 2,
    'owner' => $owner->id,
    'private' => 'true'
  ];

  $this->actingAs($owner)
    ->patch(route('room.update'), $roomSetting)
    ->assertInvalid(['private' => "The private field must be true or false."]);
});