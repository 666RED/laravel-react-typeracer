<?php

use App\Events\Room\DeleteRoom;
use App\Models\User;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;

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

  session()->put('roomId', $roomId);
  session()->put('userId', $owner->id);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('userId');
});

test('Should delete room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('userId'));
  $roomId = session('roomId');
  Event::fake();

  $this->actingAs($owner)
    ->delete(route('room.delete'))
    ->assertSessionMissing('roomId')
    ->assertRedirect(route('home'));

  //@ Redis assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId:*"));

  //@ Event assertions
  Event::assertDispatched(
    DeleteRoom::class,
    fn(DeleteRoom $event) =>
    $event->owner === $owner->id
  );

  //@ Database assertions
  assertDatabaseHas('users', [
    'id' => $owner->id,
    'room_id' => null
  ]);
});