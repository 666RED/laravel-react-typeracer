<?php

use App\Events\Message\MessageSent;
use App\Events\Room\DeleteRoom;
use App\Events\Room\JoinRoom;
use App\Events\Room\LeaveRoom;
use App\Events\Room\TransferOwnership;
use App\Models\User;
use App\Services\MessageHelperService;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  Redis::flushDb();
  //@ Create room setup & add 1 player
  $owner = User::factory()->create();
  $newOwner = User::factory()->create();
  $roomId = (string) Str::uuid();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => '2',
    'private' => '0'
  ];

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $owner);
  $owner->saveRoomId($roomId);

  $helper->addPlayer($roomId, $newOwner->id, $newOwner->name);
  $newOwner->saveRoomId($roomId);

  //@ Create another room
  $newRoomOwner = User::factory()->create();
  $newRoomId = (string) Str::uuid();
  $room2 = [
    'id' => $newRoomId,
    'name' => 'My room 2',
    'owner' => $newRoomOwner->id,
    'playerCount' => 0,
    'maxPlayer' => '2',
    'private' => false
  ];
  $helper->createRoom($newRoomId, $room2, $newRoomOwner);
  $newRoomOwner->saveRoomId($newRoomId);

  session()->put('roomId', $roomId);
  session()->put('newRoomId', $newRoomId);
  session()->put('ownerId', $owner->id);
  session()->put('newOwnerId', $newOwner->id);
  session()->put('newOwnerName', $newOwner->name);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('newRoomId');
  session()->remove('ownerId');
  session()->remove('newOwnerId');
  session()->remove('newOwnerName');
});

test('Should transfer ownership and join new room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newOwnerId = session('newOwnerId');
  $newOwnerName = session('newOwnerName');
  Event::fake();

  $this->actingAs($owner)
    ->post(route('room.transfer-and-join'), ['roomId' => $newRoomId])
    ->assertRedirect(route('room.show', ['roomId' => $newRoomId]))
    ->assertSessionHas('roomId', $newRoomId);

  //@ Redis assertions
  $messageHelper = app(MessageHelperService::class);

  $playerCount = (int) Redis::hget("room:$roomId", 'playerCount');
  $roomPlayers = Redis::zrange("room:$roomId:player", 0, -1);
  $ownerId = Redis::hget("room:$roomId", 'owner');

  $newRoomPlayerCount = (int) Redis::hget("room:$newRoomId", 'playerCount');
  $newRoomPlayers = Redis::zrange("room:$newRoomId:player", 0, -1);
  $ownerInNewRoom = Redis::hgetall("room:$newRoomId:player:$owner->id");


  $expectedOwnerInNewRoom = [
    'id' => (string) $owner->id,
    'name' => $owner->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  //@ Old room assertions
  $this->assertEquals(1, $playerCount);
  $this->assertNotContains((string) $owner->id, $roomPlayers);
  $this->assertEquals((string) $newOwnerId, $ownerId);
  $this->assertEquals(1, sizeof($messageHelper->getMessages($roomId)));

  //@ New room assertions
  $this->assertEquals(2, $newRoomPlayerCount);
  $this->assertContains((string) $owner->id, $newRoomPlayers);
  $this->assertEquals($expectedOwnerInNewRoom, $ownerInNewRoom);

  //@ Event assertions
  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) => $event->newOwner === $newOwnerId &&
      $event->newOwnerName === $newOwnerName
  );
  Event::assertDispatched(LeaveRoom::class, 1);
  Event::assertNotDispatched(DeleteRoom::class);
  Event::assertDispatched(MessageSent::class, 1);
  Event::assertDispatched(JoinRoom::class, 1);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => $newRoomId
  ]);
});

test('Should not transfer ownership and join new room: invalid newRoomId', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $this->actingAs($owner)
    ->post(route('room.transfer-and-join'), ['roomId' => 'random_room_id'])
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.']);
});

test('Should not transfer ownership and join new room: missing newRoomId', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $this->actingAs($owner)
    ->post(route('room.transfer-and-join'))
    ->assertInvalid(['roomId' => 'The room id field is required.']);
});

test('Should delete room and join new room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newOwnerId = session('newOwnerId');
  Event::fake();

  //@ Remove player (only owner in the room)
  $helper = app(RoomHelperService::class);
  $helper->removePlayer($roomId, $newOwnerId);

  $this->actingAs($owner)
    ->post(route('room.transfer-and-join'), ['roomId' => $newRoomId])
    ->assertRedirect(route('room.show', ['roomId' => $newRoomId]))
    ->assertSessionHas('roomId', $newRoomId);

  //@ Redis assertions
  $newRoomPlayerCount = (int) Redis::hget("room:$newRoomId", 'playerCount');
  $newRoomPlayers = Redis::zrange("room:$newRoomId:player", 0, -1);
  $ownerInNewRoom = Redis::hgetall("room:$newRoomId:player:$owner->id");


  $expectedOwnerInNewRoom = [
    'id' => (string) $owner->id,
    'name' => $owner->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  //@ Old room assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId:*"));

  //@ New room assertions
  $this->assertEquals(2, $newRoomPlayerCount);
  $this->assertContains((string) $owner->id, $newRoomPlayers);
  $this->assertEquals($expectedOwnerInNewRoom, $ownerInNewRoom);

  //@ Event assertions
  Event::assertNotDispatched(TransferOwnership::class);
  Event::assertDispatched(DeleteRoom::class, 1);
  Event::assertNotDispatched(MessageSent::class);
  Event::assertDispatched(JoinRoom::class, 1);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => $newRoomId
  ]);
});

test('Should transfer ownership but not join new room: new room is full', function () {
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newOwnerId = session('newOwnerId');
  $newOwnerName = session('newOwnerName');
  Event::fake();

  /** @var Tests\TestCase $this */
  //@ Add player to new room (new room is full)
  $newRoomPlayer = User::factory()->create();
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($newRoomId, $newRoomPlayer->id, $newRoomPlayer->name);

  $this->actingAs($owner)->followingRedirects()
    ->post(route('room.transfer-and-join'), ['roomId' => $newRoomId])
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Room is full')
    );

  //@ Redis assertions
  $messageHelper = app(MessageHelperService::class);

  $playerCount = (int) Redis::hget("room:$roomId", 'playerCount');
  $roomPlayers = Redis::zrange("room:$roomId:player", 0, -1);
  $ownerId = Redis::hget("room:$roomId", 'owner');

  $newRoomPlayerCount = (int) Redis::hget("room:$newRoomId", 'playerCount');
  $newRoomPlayers = Redis::zrange("room:$newRoomId:player", 0, -1);
  $ownerInNewRoom = Redis::hgetall("room:$newRoomId:player:$owner->id");

  //@ Old room assertions
  $this->assertEquals(1, $playerCount);
  $this->assertNotContains((string) $owner->id, $roomPlayers);
  $this->assertEquals((string) $newOwnerId, $ownerId);
  $this->assertEquals(1, sizeof($messageHelper->getMessages($roomId)));

  //@ New room assertions
  $this->assertEquals(2, $newRoomPlayerCount);
  $this->assertNotContains((string) $owner->id, $newRoomPlayers);
  $this->assertEmpty($ownerInNewRoom);

  //@ Event assertions
  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) => $event->newOwner === $newOwnerId &&
      $event->newOwnerName === $newOwnerName
  );
  Event::assertNotDispatched(DeleteRoom::class);
  Event::assertDispatched(MessageSent::class, 1);
  Event::assertNotDispatched(JoinRoom::class);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => null
  ]);
});

test('Should delete room but not join new room: new room is full', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newOwnerId = session('newOwnerId');
  Event::fake();

  //@ Remove player (only owner in the room)
  $helper = app(RoomHelperService::class);
  $helper->removePlayer($roomId, $newOwnerId);

  //@ Add player to new room (new room is full)
  $newRoomPlayer = User::factory()->create();
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($newRoomId, $newRoomPlayer->id, $newRoomPlayer->name);

  $this->actingAs($owner)->followingRedirects()
    ->post(route('room.transfer-and-join'), ['roomId' => $newRoomId])
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Room is full')
    );

  //@ Redis assertions
  $newRoomPlayerCount = (int) Redis::hget("room:$newRoomId", 'playerCount');
  $newRoomPlayers = Redis::zrange("room:$newRoomId:player", 0, -1);
  $ownerInNewRoom = Redis::hgetall("room:$newRoomId:player:$owner->id");


  //@ Old room assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId:*"));

  //@ New room assertions
  $this->assertEquals(2, $newRoomPlayerCount);
  $this->assertNotContains((string) $owner->id, $newRoomPlayers);
  $this->assertEmpty($ownerInNewRoom);

  //@ Event assertions
  Event::assertNotDispatched(TransferOwnership::class);
  Event::assertDispatched(DeleteRoom::class, 1);
  Event::assertNotDispatched(MessageSent::class);
  Event::assertNotDispatched(JoinRoom::class);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => null
  ]);
});
