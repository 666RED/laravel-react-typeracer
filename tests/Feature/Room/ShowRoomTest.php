<?php

use App\Models\User;
use App\Services\RoomHelperService;
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

test('Should show room', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');

  $this->actingAs($owner)
    ->get(route('room.show', [
      'roomId' => $roomId,
    ]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('room/index')
        ->has(
          'players',
          fn(Assert $players) => $players
            ->each(
              fn(Assert $player) => $player
                ->has('id')
                ->has('name')
                ->has('racesPlayed')
                ->has('averageWpm')
                ->has('racesWon')
                ->has('score')
                ->has('status')
            )
        )
        ->has('racePlayerIds', 0) //@ No race in progress
    );
});
