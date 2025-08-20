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

  //@ Create room setup (2 players in race)
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $player = User::factory()->create();
  $spectator = User::factory()->create();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 3,
    'private' => 0
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);
  $roomHelper->addPlayer($roomId, $spectator->id, $spectator->name);
  $owner->saveRoomId($roomId);
  $player->saveRoomId($roomId);
  $spectator->saveRoomId($roomId);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('spectatorId', $spectator->id);

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
  session()->remove('spectatorId');
});

test('Should show spectate race page', function () {
  /** @var Tests\TestCase $this */
  $spectator = User::find(session('spectatorId'));
  $roomId = session('roomId');

  $this->actingAs($spectator)
    ->get(route('room.spectate-race', ['roomId' => $roomId]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has('messages')
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
        ->has(
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
        )
        ->has('startTime')
        ->has(
          'quote',
          fn(Assert $quote) => $quote
            ->has('id')
            ->has('text')
        )
    );
});
