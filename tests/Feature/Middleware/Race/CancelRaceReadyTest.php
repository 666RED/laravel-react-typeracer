<?php

use App\Events\Race\ToggleReadyState;
use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
  Redis::flushdb();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  //@ Create room
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
  $player = User::factory()->create();
  $room = [
    'id' => $roomId,
    'name' => 'New room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);

  //@ Toggle player ready state to true
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

test('Should cancel player race ready', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $this->withHeader('referer', "/room/$roomId");

  $this->actingAs($player)->get(route('home'));

  //@ Redis assertions
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertDispatched(
    ToggleReadyState::class,
    fn(ToggleReadyState $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id &&
      $event->isReady === false
  );
});

test('Should not cancel player ready state: not get request', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $this->withHeader('referer', "/room/$roomId");

  $this->actingAs($player)->post(route('logout'));

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});

test('Should not cancel player ready state: no roomId', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $this->withHeader('referer', "/room/$roomId");

  session()->flush();
  $this->actingAs($player)->get(route('home'));

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});

test('Should not cancel player ready state: not authenticated', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $this->withHeader('referer', "/room/$roomId");

  $this->get(route('home'));

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});

test('Should not cancel player ready state: race started', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $ownerId = session('ownerId');
  $player = User::find(session('playerId'));
  Event::fake();

  //@ Race started
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->setRaceReady($roomId, $ownerId);

  $this->withHeader('referer', "/room/$roomId");

  $this->actingAs($player)->get(route('home'));

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});

test('Should not cancel player ready state: user refresh page', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $ownerId = session('ownerId');
  $player = User::find(session('playerId'));
  Event::fake();

  //@ Race started
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->setRaceReady($roomId, $ownerId);

  $this->withHeader('referer', "/room/$roomId");

  $this->actingAs($player)->get(route('room.show', ['roomId' => $roomId]));

  //@ Redis assertions
  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});

test('Should not cancel player ready state: user not ready', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  //@ Toggle user ready state to false
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->toggleReadyState($roomId, $player->id, true);

  $this->withHeader('referer', "/room/$roomId");

  $this->actingAs($player)->get(route('home'));

  //@ Redis assertions
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertNotDispatched(ToggleReadyState::class);
});
