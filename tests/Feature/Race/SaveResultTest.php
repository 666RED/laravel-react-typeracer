<?php

use App\Events\Race\RaceCompleted;
use App\Events\Race\RaceFinished;
use App\Events\Race\RaceNotComplete;
use App\Events\Race\RaceReady;
use App\Events\Race\SetFinishTime;
use App\Events\Race\ToggleReadyState;
use App\Events\Room\UpdatePlayerStats;
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

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\partialMock;
use function PHPSTORM_META\map;

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

  //@ Start the race
  $raceHelper->toggleReadyState($roomId, $player->id, false);
  $raceHelper->toggleReadyState($roomId, $player2->id, false);
  $raceHelper->setRaceReady($roomId, $owner->id);
  $raceHelper->setRacePlayersInitialState($roomId);

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

//@ Save result tests
test('Should save 1st place player result', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'wpm' => 60.5,
    'accuracyPercentage' => 89.52
  ];

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertStatus(204);

  //@ Redis assertions
  $expectedPlayerProgress = [
    "playerId" => (string) $player->id,
    "percentage" => "100",
    "wordsPerMinute" => (string) $result['wpm'],
    "completedCharacters" => "0",
    "wrongCharacters" => "0",
    "finished" => "1",
    "place" => "1st",
    "status" => "completed"
  ];
  $finishTime = (int) Redis::HGET("room:$roomId:race", 'finishTime');

  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:place"));
  $this->assertEquals($expectedPlayerProgress, Redis::HGETALL("room:$roomId:race:player:$player->id:progress"));
  $this->assertGreaterThan(0, $finishTime);
  $this->assertEquals(0, Redis::EXISTS("user:$player->id:profile"));
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed'));
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon'));
  $this->assertEquals($result['wpm'], (float) Redis::HGET("room:$roomId:player:$player->id", 'averageWpm'));
  $this->assertEquals(3, (int) Redis::ZSCORE("room:$roomId:player", $player->id));
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));

  //@ Event assertions
  Event::assertDispatched(
    RaceCompleted::class,
    fn(RaceCompleted $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id &&
      $event->wordsPerMinute === $result['wpm'] &&
      $event->place === '1st'
  );

  Event::assertDispatched(
    SetFinishTime::class,
    fn(SetFinishTime $event) =>
    $event->roomId === $roomId &&
      $event->finishTime === $finishTime
  );

  Event::assertDispatched(UpdatePlayerStats::class, 1);

  //@ Database assertions
  $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');
  assertDatabaseHas('race_results', [
    'user_id' => $player->id,
    'quote_id' => $quoteId,
    'wpm' => $result['wpm'],
    'place' => '1st',
    'total_players' => 3,
    'accuracy_percentage' => $result['accuracyPercentage']
  ]);
});

test('Should save 2nd place player result', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $owner = User::find(session('ownerId'));
  $roomId = session('roomId');
  Event::fake();
  $finishTime = now()->addSeconds(20)->timestamp;

  //@ Save 1st place
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->saveResult($roomId, $owner, 50, 90, $finishTime);
  $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');

  $result = [
    'wpm' => 60.5,
    'accuracyPercentage' => 89.52
  ];

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertStatus(204);

  //@ Redis assertions
  $expectedPlayerProgress = [
    "playerId" => (string) $player->id,
    "percentage" => "100",
    "wordsPerMinute" => (string) $result['wpm'],
    "completedCharacters" => "0",
    "wrongCharacters" => "0",
    "finished" => "1",
    "place" => "2nd",
    "status" => "completed"
  ];
  $finishTime = (int) Redis::HGET("room:$roomId:race", 'finishTime');

  $this->assertContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:place"));
  $this->assertEquals($expectedPlayerProgress, Redis::HGETALL("room:$roomId:race:player:$player->id:progress"));
  $this->assertGreaterThan(0, $finishTime);
  $this->assertEquals(0, Redis::EXISTS("user:$player->id:profile"));
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed'));
  $this->assertEquals(0, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon'));
  $this->assertEquals($result['wpm'], (float) Redis::HGET("room:$roomId:player:$player->id", 'averageWpm'));
  $this->assertEquals(2, (int) Redis::ZSCORE("room:$roomId:player", $player->id));
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));

  //@ Event assertions
  Event::assertDispatched(
    RaceCompleted::class,
    fn(RaceCompleted $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id &&
      $event->wordsPerMinute === $result['wpm'] &&
      $event->place === '2nd'
  );

  Event::assertNotDispatched(SetFinishTime::class);

  Event::assertDispatched(UpdatePlayerStats::class, 1);

  //@ Database assertions
  $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');
  assertDatabaseHas('race_results', [
    'user_id' => $player->id,
    'quote_id' => $quoteId,
    'wpm' => $result['wpm'],
    'place' => '2nd',
    'total_players' => 3,
    'accuracy_percentage' => $result['accuracyPercentage']
  ]);
});

test('Should save last place player result', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));
  $player2 = User::find(session('player2Id'));
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'wpm' => 60.5,
    'accuracyPercentage' => 89.52
  ];
  $finishTime = now()->addSeconds(20)->timestamp;

  //@ Save 1st place & 2nd place
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->saveResult($roomId, $owner, 50, 90, $finishTime);
  $raceHelper->saveResult($roomId, $player2, 30, 10, $finishTime);
  $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertStatus(204);

  //@ All race data is removed after last player finished the race
  $this->assertEmpty(Redis::KEYS("room:$roomId:race*"));

  $this->assertEquals(0, Redis::EXISTS("user:$player->id:profile"));
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed'));
  $this->assertEquals(0, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon'));
  $this->assertEquals($result['wpm'], (float) Redis::HGET("room:$roomId:player:$player->id", 'averageWpm'));
  $this->assertEquals(1, (int) Redis::ZSCORE("room:$roomId:player", $player->id));

  //@ Event assertions
  Event::assertDispatched(
    RaceCompleted::class,
    fn(RaceCompleted $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id &&
      $event->wordsPerMinute === $result['wpm'] &&
      $event->place === '3rd'
  );

  Event::assertNotDispatched(SetFinishTime::class);

  Event::assertDispatched(UpdatePlayerStats::class, 1);
  Event::assertDispatched(RaceFinished::class, fn($event) => $event->roomId === $roomId);

  //@ Database assertions
  assertDatabaseHas('race_results', [
    'user_id' => $player->id,
    'quote_id' => $quoteId,
    'wpm' => $result['wpm'],
    'place' => '3rd',
    'total_players' => 3,
    'accuracy_percentage' => $result['accuracyPercentage']
  ]);
});

test('Should not save guest result in database', function () {
  /** @var Tests\TestCase $this */
  //@ set player to guest
  $player = User::find(session('playerId'));
  $player->is_guest = true;
  $player->save();
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'wpm' => 60.5,
    'accuracyPercentage' => 89.52
  ];

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertStatus(204);

  //@ Database assertions (do not save guest result in db)
  assertDatabaseMissing('race_results', ['user_id' => $player->id]);
});

// ? Save result validation errors
test('Should not save player result: missing all fields', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $this->actingAs($player)
    ->post(route('race.save'))
    ->assertInvalid(['wpm', 'accuracyPercentage']);
});

test('Should not save player result: fields not in range', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $result = [
    'wpm' => -5,
    'accuracyPercentage' => 104
  ];

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertInvalid(['wpm' => "The wpm field must be at least 0.", 'accuracyPercentage' => "The accuracy percentage field must be between 0 and 100."]);
});

test('Should not save player result: invalid fields', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $result = [
    'wpm' => false,
    'accuracyPercentage' => 'hello'
  ];

  $this->actingAs($player)
    ->post(route('race.save'), $result)
    ->assertInvalid(['wpm' => "The wpm field must be a number.", 'accuracyPercentage' => "The accuracy percentage field must be a number."]);
});

//@ Save not completed result tests
test('Should save not complete player result', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'accuracyPercentage' => 91.9
  ];

  $this->actingAs($player)
    ->post(route('race.save-not-complete'), $result)
    ->assertStatus(204);

  //@ Redis assertions
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:place"));
  $this->assertEmpty(Redis::GET("user:$player->id:profile"));
  $this->assertEquals(1, Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed'));
  $this->assertEquals(0, Redis::HGET("room:$roomId:player:$player->id", 'racesWon'));
  $this->assertEquals(0, Redis::HGET("room:$roomId:player:$player->id", 'averageWpm'));
  $this->assertEquals(0, Redis::ZSCORE("room:$roomId:player", $player->id));
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));

  //@ Event assertions
  Event::assertDispatched(
    RaceNotComplete::class,
    fn(RaceNotComplete $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id
  );

  Event::assertDispatched(UpdatePlayerStats::class, 1);
  Event::assertNotDispatched(RaceFinished::class);

  //@ Database assertions
  $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');
  assertDatabaseHas('race_results', [
    'user_id' => $player->id,
    'quote_id' => $quoteId,
    'wpm' => 0,
    'place' => 'NC',
    'total_players' => 3,
    'accuracy_percentage' => $result['accuracyPercentage']
  ]);
});

test('Should remove race data in Redis after last player not complete', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));
  $player2 = User::find(session('player2Id'));
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'accuracyPercentage' => 89.52
  ];
  $finishTime = now()->addSeconds(20)->timestamp;

  //@ Save 1st place & 2nd place
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->saveResult($roomId, $owner, 50, 90, $finishTime);
  $raceHelper->saveResult($roomId, $player2, 30, 10, $finishTime);

  $this->actingAs($player)
    ->post(route('race.save-not-complete'), $result)
    ->assertStatus(204);

  //@ Redis assertions
  $this->assertEmpty(Redis::KEYS("room:$roomId:race*"));

  //@ Event assertions
  Event::assertDispatched(
    RaceFinished::class,
    fn(RaceFinished $event) =>
    $event->roomId === $roomId
  );
});

test('Should not save not complete guest result in database', function () {
  /** @var Tests\TestCase $this */
  //@ Set player to guest
  $player = User::find(session('playerId'));
  $player->is_guest = true;
  $player->save();
  $roomId = session('roomId');
  Event::fake();

  $result = [
    'accuracyPercentage' => 91.9
  ];

  $this->actingAs($player)
    ->post(route('race.save-not-complete'), $result)
    ->assertStatus(204);

  //@ Database assertions
  assertDatabaseMissing('race_results', ['user_id' => $player->id]);
});

// ? Save not complete result validation errors
test('Should not save not complete player result: missing field', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $this->actingAs($player)
    ->post(route('race.save-not-complete'))
    ->assertInvalid(['accuracyPercentage' => "The accuracy percentage field is required."]);
});

test('Should not save not complete player result: accuracyPercentage < 0', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $result = [
    'accuracyPercentage' => -1
  ];

  $this->actingAs($player)
    ->post(route('race.save-not-complete'), $result)
    ->assertInvalid(['accuracyPercentage' =>  "The accuracy percentage field must be between 0 and 100."]);
});

test('Should not save not complete player result: accuracyPercentage > 100', function () {
  /** @var Tests\TestCase $this */
  $player = User::find(session('playerId'));
  Event::fake();

  $result = [
    'accuracyPercentage' => 101
  ];

  $this->actingAs($player)
    ->post(route('race.save-not-complete'), $result)
    ->assertInvalid(['accuracyPercentage' =>  "The accuracy percentage field must be between 0 and 100."]);
});
