<?php

use App\Events\Room\DeleteRoom;
use App\Events\Room\JoinRoom;
use App\Events\Room\LeaveRoom;
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
  //@ Create room setup & add 1 player
  $owner = User::factory()->create();
  $user = User::factory()->create();
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

  $helper->addPlayer($roomId, $user->id, $user->name);
  $user->saveRoomId($roomId);

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
  session()->put('userId', $user->id);
  session()->put('userName', $user->name);
});

afterEach(function () {
  Redis::flushDb();
  session()->flush();
});

test('Should leave previous room and join new room', function () {
  /** @var Tests\TestCase $this */
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  Event::fake();

  //@ Service assertions
  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $newRoomId, $user) {
    $mock->shouldReceive('removePlayer')->with($roomId, $user->id)->andReturnFalse()->passthru();
    $mock->shouldReceive('leaveRoom')->with($roomId, $user->id, $user->name)->passthru();
    $mock->shouldReceive('joinRoom')->with($newRoomId, $user->id, $user->name)->andReturnTrue()->passthru();
  });

  $this->actingAs($user)
    ->post(route('room.leave-and-join'), ['roomId' => $newRoomId])
    ->assertRedirect(route('room.show', ['roomId' => $newRoomId]))
    ->assertSessionHas('roomId', $newRoomId);

  //@ Event assertions
  Event::assertDispatched(LeaveRoom::class, 1);
  Event::assertDispatched(JoinRoom::class, 1);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => $newRoomId
  ]);
});

test('Should delete previous room and join new room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  Event::fake();

  //@ Remove owner from the room & transfer ownership to user
  $helper = app(RoomHelperService::class);
  $helper->removePlayer($roomId, $owner->id);
  $helper->transferOwnership($roomId);

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $newRoomId, $user) {
    $mock->shouldReceive('removePlayer')->with($roomId, $user->id)->andReturnTrue()->passthru();
    $mock->shouldNotReceive('leaveRoom');
    $mock->shouldReceive('joinRoom')->with($newRoomId, $user->id, $user->name)->andReturnTrue()->passthru();
  });

  $this->actingAs($user)
    ->post(route('room.leave-and-join'), ['roomId' => $newRoomId])
    ->assertRedirect(route('room.show', ['roomId' => $newRoomId]))
    ->assertSessionHas('roomId', $newRoomId);

  //@ Event assertions
  Event::assertNotDispatched(LeaveRoom::class);
  Event::assertDispatched(DeleteRoom::class, 1);
  Event::assertDispatched(JoinRoom::class, 1);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => $newRoomId
  ]);
});

test('Should delete previous room but not join new room: room is full', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newPlayer = User::factory()->create();
  Event::fake();

  //@ Remove owner from the room & transfer ownership to user
  $helper = app(RoomHelperService::class);
  $helper->removePlayer($roomId, $owner->id);
  $helper->transferOwnership($roomId);

  //@ Add new player to new room (room is full)
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($newRoomId, $newPlayer->id, $newPlayer->name);

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $newRoomId, $user) {
    $mock->shouldReceive('removePlayer')->with($roomId, $user->id)->andReturnTrue()->passthru();
    $mock->shouldNotReceive('leaveRoom');
    $mock->shouldReceive('joinRoom')->with($newRoomId, $user->id, $user->name)->andReturnFalse()->passthru();
  });

  $this->actingAs($user)->followingRedirects()
    ->post(route('room.leave-and-join'), ['roomId' => $newRoomId])
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Room is full')
    );

  //@ Event assertions
  Event::assertNotDispatched(LeaveRoom::class);
  Event::assertDispatched(DeleteRoom::class, 1);
  Event::assertNotDispatched(JoinRoom::class);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});

test('Should not leave previous room and join new room: invalid newRoomId', function () {
  /** @var Tests\TestCase $this */
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  $newRoomId = 'random_new_room_id';
  Event::fake();

  $this->actingAs($user)
    ->post(route('room.leave-and-join'), ['roomId' => $newRoomId])
    ->assertInvalid(['roomId' => "The room id field must be a valid UUID."])
    ->assertSessionHas('roomId', $roomId);
});

test('Should not leave previous room and join new room: missing newRoomId', function () {
  /** @var Tests\TestCase $this */
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  Event::fake();

  $this->actingAs($user)
    ->post(route('room.leave-and-join'))
    ->assertInvalid(['roomId' => "The room id field is required."])
    ->assertSessionHas('roomId', $roomId);
});

test('Should leave previous room but not join new room: room is full', function () {
  /** @var Tests\TestCase $this */
  $user = User::find(session('userId'));
  $roomId = session('roomId');
  $newRoomId = session('newRoomId');
  $newPlayer = User::factory()->create();
  Event::fake();

  //@ Add new player to new room (room is full)
  $helper = app(RoomHelperService::class);
  $helper->addPlayer($newRoomId, $newPlayer->id, $newPlayer->name);

  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId, $newRoomId, $user) {
    $mock->shouldReceive('removePlayer')->once()->with($roomId, $user->id)->andReturnFalse()->passthru();
    $mock->shouldReceive('leaveRoom')->once()->with($roomId, $user->id, $user->name)->passthru();
    $mock->shouldReceive('joinRoom')->with($newRoomId, $user->id, $user->name)->andReturnFalse()->passthru();
  });

  $this->actingAs($user)->followingRedirects()
    ->post(route('room.leave-and-join'), ['roomId' => $newRoomId])
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Room is full')
    );

  //@ Event assertions
  Event::assertDispatched(LeaveRoom::class, 1);
  Event::assertNotDispatched(JoinRoom::class);

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $user->id,
    'room_id' => null
  ]);
});