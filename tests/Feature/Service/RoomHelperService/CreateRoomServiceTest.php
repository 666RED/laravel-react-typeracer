<?php

use App\Events\Room\NewRoomCreated;
use App\Models\User;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;

use function Pest\Laravel\partialMock;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
});

test('Should create public room', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $user = User::factory()->create();
  Event::fake();

  $room = [
    'id' => $roomId,
    'name' => 'New room',
    'owner' => $user->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $user) {
    $mock->shouldReceive('addPlayer')->once()->with($roomId, $user->id, $user->name)->passthru();
  });

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $user);

  //@ Redis assertions
  $expectedRoom = [
    'id' => $room['id'],
    'name' => $room['name'],
    'owner' => (string) $room['owner'],
    'playerCount' => '1',
    'maxPlayer' => (string) $room['maxPlayer'],
    'private' => $room['private'],
  ];

  $this->assertEquals($expectedRoom, Redis::HGETALL("room:$roomId"));
  $this->assertContains($roomId, Redis::SMEMBERS("available-rooms"));

  $room['playerCount'] = 1;

  //@ Event assertions
  Event::assertDispatched(
    NewRoomCreated::class,
    fn(NewRoomCreated $event) =>
    $event->room === $room
  );
});

test('Should create private room', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $user = User::factory()->create();
  Event::fake();

  $room = [
    'id' => $roomId,
    'name' => 'New room',
    'owner' => $user->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '1'
  ];

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $user) {
    $mock->shouldReceive('addPlayer')->once()->with($roomId, $user->id, $user->name)->passthru();
  });

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $user);

  //@ Redis assertions
  $expectedRoom = [
    'id' => $room['id'],
    'name' => $room['name'],
    'owner' => (string) $room['owner'],
    'playerCount' => '1',
    'maxPlayer' => (string) $room['maxPlayer'],
    'private' => $room['private'],
  ];

  $this->assertEquals($expectedRoom, Redis::HGETALL("room:$roomId"));
  $this->assertContains($roomId, Redis::SMEMBERS("available-rooms"));

  //@ Event assertions
  Event::assertNotDispatched(NewRoomCreated::class);
});
