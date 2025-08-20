<?php

use App\Events\Room\DeleteRoom;
use App\Events\Room\JoinRoom;
use App\Events\Room\LeaveRoom;
use App\Events\Room\TransferOwnership;
use App\Helpers\RoomHelper;
use App\Models\User;
use App\Services\MessageHelperService;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;

use function Pest\Laravel\partialMock;

beforeEach(function () {
  Redis::flushdb();

  //@ Create room
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $room = [
    'id' => $roomId,
    'name' => 'New room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '1'
  ];

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $owner);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
});

afterEach(function () {
  Redis::flushdb();
  session()->remove('roomId');
  session()->remove('ownerId');
});

test('Should resolve RoomHelper facade', function () {
  /** @var Tests\TestCase $this */
  $instance = RoomHelper::getFacadeRoot();
  $this->assertInstanceOf(RoomHelperService::class, $instance);
});

test('Should not join room: already in the room', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $user = User::factory()->create();
  Event::fake();

  //@ Add player
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $user->id, $user->name);

  partialMock(RoomHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('addPlayer');
  });

  $result = $helper->joinRoom($roomId, $user->id, $user->name);

  //@ Service assertions
  $this->assertTrue($result);

  //@ Event assertions
  Event::assertNotDispatched(JoinRoom::class);

  //@ Redis assertions
  $this->assertNotNull(Redis::ZSCORE("room:$roomId:player", $user->id));
});

test('Should not join room: room is full', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::factory()->create();
  $user = User::factory()->create();
  Event::fake();

  //@ Add player
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $player->id, $player->name);

  partialMock(RoomHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('addPlayer');
  });

  //@ Service assertions
  $result = $helper->joinRoom($roomId, $user->id, $user->name);

  $this->assertFalse($result);

  //@ Event assertions
  Event::assertNotDispatched(JoinRoom::class);

  //@ Redis assertions
  $this->assertNull(Redis::ZSCORE("room:$roomId:player", $user->id));
});

test('Should transfer ownership', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $player = User::factory()->create();
  $roomId = session('roomId');
  Event::fake();

  //@ Add player
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $player->id, $player->name);

  //@ Remove owner from room
  $helper->removePlayer($roomId, $owner->id);

  partialMock(MessageHelperService::class, function (MockInterface $mock) use ($roomId, $player) {
    $mock->shouldReceive('pushMessage')->once()->with($roomId, [
      'text' => "$player->name is the room owner now",
      'senderId' => $player->id,
      'senderName' => $player->name,
      'isNotification' => true
    ])->passthru();
  });

  $helper->transferOwnership($roomId);

  //@ Redis assertions
  $this->assertEquals((string) $player->id, Redis::HGET("room:$roomId", 'owner'));
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) =>
    $event->roomId === $roomId &&
      $event->newOwner === $player->id &&
      $event->newOwnerName === $player->name
  );
});

test('Should leave room', function () {
  /** @var Tests\TestCase $this */
  $player = User::factory()->create();
  $roomId = session('roomId');
  Event::fake();

  //@ Add player
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $player->id, $player->name);

  $helper->leaveRoom($roomId, $player->id, $player->name);

  $playerCount = (int) Redis::HGET("room:$roomId", 'playerCount');

  //@ Event assertions
  Event::assertDispatched(
    LeaveRoom::class,
    fn(LeaveRoom $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id &&
      $event->playerName === $player->name &&
      $event->playerCount === $playerCount
  );
});

test('Should delete room if no player in the room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldReceive('deleteRoomIfNoPlayerInTheRoom')->once()->with($roomId)->andReturnTrue()->passthru();
    $mock->shouldReceive('removeRoom')->once()->with($roomId)->passthru();
  });

  //@ Remove owner
  $helper = app(RoomHelperService::class);
  $result = $helper->removePlayer($roomId, $owner->id);

  //@ Service assertions
  $this->assertTrue($result);

  //@ Event assertions
  Event::assertDispatched(
    DeleteRoom::class,
    fn(DeleteRoom $event) =>
    $event->roomId === $roomId &&
      $event->owner === $owner->id
  );

  //@ Redis assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId*"));
});

test('Should not delete room if at least one player in the room', function () {
  /** @var Tests\TestCase $this */
  $player = User::factory()->create();
  $roomId = session('roomId');
  Event::fake();

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldReceive('deleteRoomIfNoPlayerInTheRoom')->once()->with($roomId)->andReturnFalse()->passthru();
    $mock->shouldNotReceive('removeRoom');
  });

  //@ Add player
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $player->id, $player->name);

  $result = $helper->removePlayer($roomId, $player->id);

  //@ Service assertions
  $this->assertFalse($result);

  //@ Event assertions
  Event::assertNotDispatched(DeleteRoom::class);

  //@ Redis assertions
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertNotContains((string) $player->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
});

test('Should add player', function () {
  /** @var Tests\TestCase $this */
  $player = User::factory()->create();
  $roomId = session('roomId');

  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $player->id, $player->name);

  //@ Redis assertions
  $expectedPlayer = [
    'id' => (string) $player->id,
    'name' => $player->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  $this->assertEquals(2, (int) Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertContains((string) $player->id, Redis::ZRANGE("room:$roomId:player", 0, -1));
  $this->assertEquals($expectedPlayer, Redis::HGETALL("room:$roomId:player:$player->id"));
});

test('Should get player', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');

  //@ Service assertions
  $expectedPlayer = [
    'id' => $owner->id,
    'name' => $owner->name,
    'racesPlayed' => 0,
    'averageWpm' => 0,
    'racesWon' => 0
  ];

  $helper = app(RoomHelperService::class);
  $player = $helper->getPlayer($roomId, $owner->id);

  $this->assertEquals($expectedPlayer, $player);
});