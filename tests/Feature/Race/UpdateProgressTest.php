<?php

use App\Events\Race\RaceReady;
use App\Events\Race\UpdateProgress;
use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;

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

  $room = [
    'id' => $roomId,
    'name' => 'My room',
    'owner' => $owner->id,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => 0
  ];

  //@ Add owner and player into the room
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);
  $owner->saveRoomId($roomId);
  $player->saveRoomId($roomId);

  $raceHelper = app(RaceHelperService::class);

  //@ Set player isReady to true
  $raceHelper->toggleReadyState($roomId, $player->id, false);
  $raceHelper->setRaceReady($roomId, $owner->id);
  $raceHelper->setRacePlayersInitialState($roomId);

  session()->put('roomId', $roomId);
  session()->put('playerId', $player->id);
});

afterEach(function () {
  Redis::flushDb();
  session()->remove('roomId');
  session()->remove('playerId');
});

test('Should update player progress', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $progress = [
    'percentage' => 10.0,
    'wordsPerMinute' => 60.0,
    'completedCharacters' => 20,
    'wrongCharacters' => 0
  ];

  $this->actingAs($player)
    ->post(route('race.update-progress'), $progress)
    ->assertStatus(204);

  //@ Event assertions
  Event::assertDispatched(
    UpdateProgress::class,
    fn(UpdateProgress $event) =>
    $event->roomId === $roomId &&
      $event->userId === $player->id &&
      $event->percentage === $progress['percentage'] &&
      $event->wordsPerMinute === $progress['wordsPerMinute']
  );

  //@ Redis assertions
  $expectedPlayerProgress = [
    'playerId' => (string) $player->id,
    'percentage' => (string) $progress['percentage'],
    'wordsPerMinute' => (string) $progress['wordsPerMinute'],
    'completedCharacters' => (string) $progress['completedCharacters'],
    'wrongCharacters' => (string) $progress['wrongCharacters'],
    'finished' => '0',
    'place' => '0',
    'status' => 'play'
  ];

  $this->assertEquals($expectedPlayerProgress, Redis::HGETALL("room:$roomId:race:player:$player->id:progress"));
});

//@ Redis and Event assertions
test('Should not modify Redis & dispatch event', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Event::fake();

  $this->actingAs($player)
    ->post(route('race.update-progress'))
    ->assertInvalid(['percentage', 'wordsPerMinute', 'completedCharacters', 'wrongCharacters']);

  //@ Redis assertions
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

  $this->assertEquals($expectedPlayerProgress, Redis::HGETALL("room:$roomId:race:player:$player->id:progress"));

  //@ Event assertions
  Event::assertNotDispatched(UpdateProgress::class);
});

//@ Validation errors
test('Should not update player progress: missing all fields', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $this->actingAs($player)
    ->post(route('race.update-progress'))
    ->assertInvalid(['percentage', 'wordsPerMinute', 'completedCharacters', 'wrongCharacters']);
});

test('Should not update player progress: invalid fields', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $progress = [
    'percentage' => 100.1,
    'wordsPerMinute' => null,
    'completedCharacters' => 20.1,
    'wrongCharacters' => false
  ];

  $this->actingAs($player)
    ->post(route('race.update-progress'), $progress)
    ->assertInvalid([
      'percentage' => "The percentage field must be between 0 and 100.",
      'wordsPerMinute' => "The words per minute field is required.",
      'completedCharacters' => 'The completed characters field must be an integer.',
      'wrongCharacters' => 'The wrong characters field must be an integer.'
    ]);
});
