<?php

use App\Events\Room\DeleteRoom;
use App\Events\Room\LeaveRoom;
use App\Models\User;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  Redis::flushDb();
  //@ Create room setup
  $user = User::factory()->create();
  $roomId = (string) Str::uuid();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $user->id,
    'playerCount' => 0,
    'maxPlayer' => '2',
    'private' => '0'
  ];

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $user);
  $user->saveRoomId($roomId);

  session()->put('joinRoomId', $roomId);
  session()->put('userId', $user->id);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('joinRoomId');
  session()->remove('userId');
});

test('Should leave room', function () {
  /** @var Tests\TestCase $this */
  Event::fake();

  //@ Join room setup
  $user = User::factory()->create();
  $roomId = session('joinRoomId');

  $helper = app(RoomHelperService::class);
  $helper->addPlayer($roomId, $user->id, $user->name);
  session()->put('roomId', $roomId);
  $user->saveRoomId($roomId);

  //@ Test leave room
  $this->actingAs($user)
    ->post(route('room.leave'))
    ->assertRedirect(route('home'))
    ->assertSessionMissing('roomId');

  //@ Event assertions
  Event::assertDispatched(LeaveRoom::class, 1);
  Event::assertNotDispatched(DeleteRoom::class);

  //@ Redis assertion
  $this->assertEquals('1', Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertNotContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, -1));

  //@ Database assertion
  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});

test('Should leave room & delete room', function () {
  /** @var Tests\TestCase $this */
  Event::fake();

  $user = User::find(session('userId'));
  $roomId = session('joinRoomId');

  $this->actingAs($user)
    ->post(route('room.leave'))
    ->assertRedirect(route('home'))
    ->assertSessionMissing('roomId');

  //@ Event assertions
  Event::assertNotDispatched(LeaveRoom::class);
  Event::assertDispatched(DeleteRoom::class, 1);

  //@ Redis assertion
  $this->assertEmpty(Redis::KEYS("room:$roomId:*"));

  //@ Database assertion
  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});
