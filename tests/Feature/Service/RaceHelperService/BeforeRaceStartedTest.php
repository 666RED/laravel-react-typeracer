<?php

use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
  Redis::flushdb();
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  //@ Create room & add player
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $player = User::factory()->create();
  $room = [
    'id' => $roomId,
    'name' => 'New room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '1'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);

  //@ Set player to be ready
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->toggleReadyState($roomId, $player->id, false);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('playerId', $player->id);
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should set race to be ready', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $helper->setRaceReady($roomId, $owner->id);

  //@ Redis assertions
  $playerIds = $helper->getPlayerIds($roomId);

  $this->assertContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:player"));
  $this->assertGreaterThan(0, (int) Redis::HGET("room:$roomId:race", 'startTime'));
  $this->assertNotNull(Redis::HGET("room:$roomId:race", 'quoteId'));
  $this->assertEquals([(string) $owner->id, (string) $player->id], Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
});

test('Should get player ids', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $playerIds = $helper->getPlayerIds($roomId);

  $this->assertEquals([$player->id], $playerIds);
});

test('Should set race players initial state', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  //@ Add owner to the race
  $helper = app(RaceHelperService::class);
  $helper->setRaceReady($roomId, $owner->id);

  $expectedPlayerInitialState = [
    'playerId' => (string) $player->id,
    'percentage' => '0',
    'wordsPerMinute' => '0',
    'completedCharacters' => '0',
    'wrongCharacters' => '0',
    'finished' => '0',
    'place' => '0',
    'status' => 'play'
  ];

  $expectedOwnerInitialState = [
    'playerId' => (string) $owner->id,
    'percentage' => '0',
    'wordsPerMinute' => '0',
    'completedCharacters' => '0',
    'wrongCharacters' => '0',
    'finished' => '0',
    'place' => '0',
    'status' => 'play'
  ];

  $helper->setRacePlayersInitialState($roomId);

  //@ Redis assertions
  $this->assertEquals($expectedPlayerInitialState, Redis::HGETALL("room:$roomId:race:player:$player->id:progress"));
  $this->assertEquals($expectedOwnerInitialState, Redis::HGETALL("room:$roomId:race:player:$owner->id:progress"));
});

test('Should toggle player ready state from false to true', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $helper->toggleReadyState($roomId, $player->id, false);

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));
});

test('Should toggle player ready state from true to false', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $helper->toggleReadyState($roomId, $player->id, true);

  //@ Redis assertions
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));
});
