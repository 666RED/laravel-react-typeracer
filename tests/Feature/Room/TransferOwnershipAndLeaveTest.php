<?php

use App\Events\Message\MessageSent;
use App\Events\Room\LeaveRoom;
use App\Events\Room\TransferOwnership;
use App\Models\User;
use App\Services\MessageHelperService;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

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

  session()->put('roomId', $roomId);
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

test('Should transfer ownership and leave room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newOwnerId = session('newOwnerId');
  $newOwnerName = session('newOwnerName');
  Event::fake();

  $this->actingAs($owner)
    ->post(route('room.transfer-and-leave'))
    ->assertRedirect(route('home'))
    ->assertSessionMissing('roomId');

  //@ Redis assertions
  $messageHelper = app(MessageHelperService::class);

  $playerCount = (int) Redis::hget("room:$roomId", 'playerCount');
  $roomPlayers = Redis::zrange("room:$roomId:player", 0, -1);
  $ownerId = Redis::hget("room:$roomId", 'owner');

  $this->assertEquals(1, $playerCount);
  $this->assertNotContains((string) $owner->id, $roomPlayers);
  $this->assertEquals((string) $newOwnerId, $ownerId);
  $this->assertEquals(1, sizeof($messageHelper->getMessages($roomId)));

  //@ Event assertions
  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) => $event->newOwner === $newOwnerId &&
      $event->newOwnerName === $newOwnerName
  );
  Event::assertDispatched(LeaveRoom::class, 1);
  Event::assertDispatched(MessageSent::class, 1);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => null
  ]);
});

test('Should delete room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $newOwnerId = session('newOwnerId');
  Event::fake();

  //@ Remove player (only owner in the room)
  $helper = app(RoomHelperService::class);
  $helper->removePlayer($roomId, $newOwnerId);

  $this->actingAs($owner)
    ->post(route('room.transfer-and-leave'))
    ->assertRedirect(route('home'))
    ->assertSessionMissing('roomId');

  //@ Redis assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId:*"));

  //@ Event assertions
  Event::assertNotDispatched(TransferOwnership::class);
  Event::assertNotDispatched(LeaveRoom::class);
  Event::assertNotDispatched(MessageSent::class);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => null
  ]);
});