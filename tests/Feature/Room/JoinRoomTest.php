<?php

use App\Events\Room\JoinRoom;
use App\Models\User;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\partialMock;

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
    'maxPlayer' => '2',
    'private' => '0'
  ];

  $helper = app(RoomHelperService::class);
  $helper->createRoom($roomId, $room, $owner);
  $owner->saveRoomId($roomId);

  session()->put('joinRoomId', $roomId);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('joinRoomId');
});

test('Should join room', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $roomId = session('joinRoomId');
  Event::fake();

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertRedirect(route('room.show', ['roomId' => $roomId]))
    ->assertSessionHas('roomId', $roomId);

  //@ Broadcast event assertions
  Event::assertDispatched(JoinRoom::class, 1);

  //@ Redis assertions
  $expectedPlayer = [
    'id' => (string) $user->id,
    'name' => $user->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  $this->assertEquals('2', Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, 1));
  $this->assertEquals($expectedPlayer, Redis::HGETALL("room:$roomId:player:$user->id"));

  //@ Database assertions
  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => $roomId
  ]);
});

test('Should not join room: invalid roomId', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => 'random_room_id'])
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.'])
    ->assertSessionMissing('roomId');
});

test('Should join room: already joined', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $roomId = session('joinRoomId');
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->addPlayer($roomId, $user->id, $user->name);
  session()->put('roomId', $roomId);
  $user->saveRoomId($roomId);

  //@ Helper service assertion
  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $user) {
    $mock
      ->shouldNotReceive('addPlayer')
      ->with($roomId, $user->id, $user->name)
      ->passthru();
  });

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertRedirect(route('room.show', ['roomId' => $roomId]))
    ->assertSessionHas('roomId', $roomId);

  //@ Redis assertion
  $playerInRoom = [
    'id' => (string) $user->id,
    'name' => $user->name,
    'racesPlayed' => '0',
    'averageWpm' => '0',
    'racesWon' => '0'
  ];

  $this->assertEquals('2', Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, 1));
  $this->assertEquals($playerInRoom, Redis::HGETALL("room:$roomId:player:$user->id"));
});

test('Should not modify Redis', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $roomId = 'random_room_id';

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.'])
    ->assertSessionMissing('roomId');

  //@ Redis assertions
  $this->assertEmpty(Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertEmpty(Redis::ZRANGE("room:$roomId:player", 0, 1));
  $this->assertEmpty(Redis::HGETALL("room:$roomId:player:$user->id"));
});

test('Should not modify user room_id column', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $roomId = 'random_room_id';

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.'])
    ->assertSessionMissing('roomId');

  //@ Database assertions
  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});

test('Should not dispatch JoinRoom event', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $roomId = 'random_room_id';
  Event::fake();

  $this->actingAs($user)
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertInvalid(['roomId' => 'The room id field must be a valid UUID.'])
    ->assertSessionMissing('roomId');

  //@ Broadcast event assertions
  Event::assertNotDispatched(JoinRoom::class);
});

test('Should not join room: room is full', function () {
  /** @var Tests\TestCase $this */
  //@ Add one more player into the room
  $player = User::factory()->create();
  $roomId = session('joinRoomId');
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);

  //@ Test room is full
  $user = User::factory()->create();
  Event::fake();

  $this->actingAs($user)->followingRedirects()
    ->post(route('room.join'), ['roomId' => $roomId])
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Room is full')
    )
    ->assertSessionMissing('roomId');

  //@ Event assertions
  Event::assertNotDispatched(JoinRoom::class);

  //@ Redis assertions
  $this->assertEquals('2', Redis::HGET("room:$roomId", 'playerCount'));
  $this->assertNotContains((string) $user->id, Redis::ZRANGE("room:$roomId:player", 0, 1));
  $this->assertEmpty(Redis::HGETALL("room:$roomId:player:$user->id"));

  //@ Database assertions
  $user->refresh();
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});
