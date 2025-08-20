<?php

use App\Events\Race\RaceReady;
use App\Events\Race\ToggleReadyState;
use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Testing\Fluent\AssertableJson as JsonAssert;

use function Pest\Laravel\partialMock;

beforeEach(function () {
  Redis::flushDb();

  //@ Seed quote
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  //@ Create room setup
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $player = User::factory()->create();
  $player2 = User::factory()->create();

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 3,
    'private' => 0
  ];

  //@ Add owner and player into the room
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);
  $roomHelper->addPlayer($roomId, $player2->id, $player2->name);
  $owner->saveRoomId($roomId);
  $player->saveRoomId($roomId);
  $player2->saveRoomId($roomId);

  $raceHelper = app(RaceHelperService::class);

  //@ Set player isReady to true
  $raceHelper->toggleReadyState($roomId, $player->id, false);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('playerId', $player->id);
  session()->put('player2Id', $player2->id);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('ownerId');
  session()->remove('playerId');
  session()->remove('player2Id');
});

test('Should set race to ready', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Helper service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldReceive('resetAll')->once()->with($roomId)->passthru();
  });

  $this->actingAs($owner)
    ->post(route('race.race-ready'))
    ->assertStatus(204); //@ noContent

  //@ Redis assertions
  $expectedOwnerProgress = [
    'playerId' => (string) $owner->id,
    'percentage' => '0',
    'wordsPerMinute' => '0',
    'completedCharacters' => '0',
    'wrongCharacters' => '0',
    'finished' => '0',
    'place' => '0',
    'status' => 'play'
  ];

  $expectedPlayerProgress = [
    'playerId' => (string) $player->id,
    'percentage' => '0',
    'wordsPerMinute' => '0',
    'completedCharacters' => '0',
    'wrongCharacters' => '0',
    'finished' => '0',
    'place' => '0',
    'status' => 'play'
  ];

  $playerSets = Redis::SMEMBERS("room:$roomId:race:player");
  $inRacePlayerSets = Redis::SMEMBERS("room:$roomId:race:inRacePlayer");
  $raceHash = Redis::HGETALL("room:$roomId:race");
  $ownerProgress = Redis::HGETALL("room:$roomId:race:player:$owner->id:progress");
  $playerProgress = Redis::HGETALL("room:$roomId:race:player:$player->id:progress");

  $this->assertContains((string) $owner->id, $playerSets);
  $this->assertContains((string) $player->id, $playerSets);
  $this->assertContains((string) $owner->id, $inRacePlayerSets);
  $this->assertContains((string) $player->id, $inRacePlayerSets);
  $this->assertNotEquals(0, (int) $raceHash['startTime']);
  $this->assertNotNull($raceHash['quoteId']);
  $this->assertEquals($expectedOwnerProgress, $ownerProgress);
  $this->assertEquals($expectedPlayerProgress, $playerProgress);

  //@ Event assertions
  Event::assertDispatched(RaceReady::class, 1);
});

test('Should toggle ready state: true -> false', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');
  Event::fake();

  $this->actingAs($player)
    ->post(route('race.toggle-ready-state'))
    ->assertStatus(204);

  //@ Event assertions
  Event::assertDispatched(ToggleReadyState::class, 1);

  //@ Redis assertions
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));
});

test('Should toggle ready state: false -> true', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');
  Event::fake();

  //@ Toggle ready state to false
  Redis::SREM("room:$roomId:race:player", $player->id);

  $this->actingAs($player)
    ->post(route('race.toggle-ready-state'))
    ->assertStatus(204);

  //@ Event assertions
  Event::assertDispatched(ToggleReadyState::class, 1);

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));
});

test('Should not toggle ready state: owner is prohibited', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  Event::fake();

  $this->actingAs($owner)->followingRedirects()
    ->post(route('race.toggle-ready-state'))
    ->assertInertia(
      fn(Assert $page) => $page
        ->whereContains('errors', 'Owner is not allowed to toggle ready state')
    );

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});
