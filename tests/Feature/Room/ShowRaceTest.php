<?php

use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Carbon\Carbon;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
  Redis::flushDb();

  //@ Seed quote
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  //@ Create room setup
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $player = User::factory()->create();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => 0
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);
  $owner->saveRoomId($roomId);
  $player->saveRoomId($roomId);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('playerId', $player->id);

  $raceHelper = app(RaceHelperService::class);

  //@ Set player isReady to true
  $raceHelper->toggleReadyState($roomId, $player->id, false);

  //@ Set race ready
  $raceHelper->setRaceReady($roomId, $owner->id);
  $raceHelper->setRacePlayersInitialState($roomId);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('ownerId');
  session()->remove('playerId');
});

test('Should show race', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');

  $this->actingAs($owner)->followingRedirects()
    ->get(route('room.show-race', ['roomId' => $roomId]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('room/race')
        ->has('startTime')
        ->has('finishTime')
        ->has(
          'quote',
          fn(Assert $quote) => $quote
            ->has('id')
            ->has('text')
        )->has(
          'inRacePlayers',
          fn(Assert $players) => $players
            ->each(
              fn(Assert $player) => $player
                ->has('id')
                ->has('name')
                ->has('percentage')
                ->has('wordsPerMinute')
                ->has('finished')
                ->has('place')
                ->has('status')
            )
        )->has('completedCharacters')
        ->has('wrongCharacters')
    );
});

test('Should not show race: race aborted', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  $finishTime = now()->addSeconds(10)->timestamp;

  //@ Set owner to complete race
  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $owner, 40, 90, $finishTime);

  //@ Remove player from inRacePlayer & set progress status to 'abort'
  Redis::SREM("room:$roomId:race:inRacePlayer", $player->id);
  Redis::HSET("room:$roomId:race:player:$player->id:progress", 'status', 'abort');

  $this->actingAs($player)->followingRedirects()
    ->get(route('room.show-race', ['roomId' => $roomId]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('room/index')
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'You have aborted the race')
            ->where('type', 'warning')
        )
    );
});

test('Should not show race: race completed', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');

  //@ Remove player from inRacePlayer & set progress status to 'completed'
  Redis::SREM("room:$roomId:race:inRacePlayer", $player->id);
  Redis::HSET("room:$roomId:race:player:$player->id:progress", 'status', 'completed');

  $this->actingAs($player)->followingRedirects()
    ->get(route('room.show-race', ['roomId' => $roomId]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('room/index')
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'You have completed the race')
            ->whereNull('type')
        )
    );
});

test('Should not show race: race aborted -> exceed finish time', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');

  //@ Set finishTime to less than now()
  Redis::HSET("room:$roomId:race", 'finishTime', Carbon::now()->timestamp - 10);

  $this->actingAs($player)->followingRedirects()
    ->get(route('room.show-race', ['roomId' => $roomId]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('room/index')
        ->whereContains('errors', 'Race aborted')
    );

  //@ Redis assertions
  $this->assertNotContains((string)$player->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
  $this->assertEquals('abort', Redis::HGET("room:$roomId:race:player:$player->id:progress", 'status'));
});
