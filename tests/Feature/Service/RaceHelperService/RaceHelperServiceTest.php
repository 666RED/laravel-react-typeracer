<?php

use App\Events\Race\AbortRace;
use App\Events\Race\RaceFinished;
use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\partialMock;

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

  //@ Start race
  $raceHelper->setRaceReady($roomId, $owner->id);
  $raceHelper->setRacePlayersInitialState($roomId);

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('playerId', $player->id);
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should get in race player ids', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $result = $helper->getInRacePlayerIds($roomId);

  //@ Service assertions
  $this->assertEquals([$owner->id, $player->id], $result);
});

test('Should get all in race players in play state', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $result = $helper->getAllPlayers($roomId);

  $expectedInRacePlayers = [
    [
      'id' => $owner->id,
      'name' => $owner->name,
      'percentage' => 0,
      'wordsPerMinute' => 0,
      'finished' => false,
      'place' => '',
      'status' => 'play'
    ],
    [
      'id' => $player->id,
      'name' => $player->name,
      'percentage' => 0,
      'wordsPerMinute' => 0,
      'finished' => false,
      'place' => '',
      'status' => 'play'
    ]
  ];

  //@ Service assertions
  $this->assertEquals($expectedInRacePlayers, $result);
});

test('Should get all in race players, one in play state, one in completed state', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  //@ Set player race state to 'completed'
  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $player, 80.2, 94.0, now()->addSeconds(20)->timestamp);

  $result = $helper->getAllPlayers($roomId);

  $expectedInRacePlayers = [
    [
      'id' => $owner->id,
      'name' => $owner->name,
      'percentage' => 0,
      'wordsPerMinute' => 0,
      'finished' => false,
      'place' => '',
      'status' => 'play'
    ],
    [
      'id' => $player->id,
      'name' => $player->name,
      'percentage' => 100,
      'wordsPerMinute' => 80.2,
      'finished' => true,
      'place' => '1st',
      'status' => 'completed'
    ]
  ];

  //@ Service assertions
  $this->assertEquals($expectedInRacePlayers, $result);
});

test('Should get all in race players, one in play state, one in abort state', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  //@ Set player race state to 'completed'
  $helper = app(RaceHelperService::class);
  $helper->abortRace($roomId, $player);

  $result = $helper->getAllPlayers($roomId);

  $expectedInRacePlayers = [
    [
      'id' => $owner->id,
      'name' => $owner->name,
      'percentage' => 0,
      'wordsPerMinute' => 0,
      'finished' => false,
      'place' => '',
      'status' => 'play'
    ],
    [
      'id' => $player->id,
      'name' => $player->name,
      'percentage' => 0.0,
      'wordsPerMinute' => 0.0,
      'finished' => false,
      'place' => '',
      'status' => 'abort'
    ]
  ];

  //@ Service assertions
  $this->assertEquals($expectedInRacePlayers, $result);
});

test('Should get room players', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  $helper = app(RaceHelperService::class);
  $result = $helper->getRoomPlayers($roomId);

  //@ service assertions
  $expectedRoomPlayers = [
    [
      'id' => $owner->id,
      'name' => $owner->name,
      'racesPlayed' => 0,
      'averageWpm' => 0.0,
      'racesWon' => 0,
      'score' => 0,
      'status' => 'play'
    ],
    [
      "id" => $player->id,
      "name" => $player->name,
      "racesPlayed" => 0,
      "averageWpm" => 0.0,
      "racesWon" => 0,
      "score" => 0,
      "status" => 'play'
    ]
  ];

  $this->assertEquals($expectedRoomPlayers, $result);
});

test('Should get race place', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));

  //@ Owner won
  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $owner, 90.0, 90.0, now()->addSeconds(20)->timestamp);

  $result = $helper->getRacePlace($roomId);

  $this->assertEquals('1st', $result);
});

test('Should abort user race', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  Event::fake();

  //@ Service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $owner) {
    $mock->shouldReceive('saveNotCompleteResult')->once()->with($roomId, $owner, 90.0);
  });

  //@  Set completedCharacters and wrongCharacters
  $helper = app(RaceHelperService::class);
  Redis::HMSET("room:$roomId:race:player:$owner->id:progress", [
    'completedCharacters' => 9,
    'wrongCharacters' => 1
  ]);

  $helper->abortRace($roomId, $owner);

  //@ Redis assertions
  $this->assertNotContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
  $this->assertEquals('abort', Redis::HGET("room:$roomId:race:player:$owner->id:progress", 'status'));

  //@ Event assertions
  Event::assertDispatched(
    AbortRace::class,
    fn(AbortRace $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $owner->id
  );
  Event::assertNotDispatched(RaceFinished::class);
});

test('Should abort guest race', function () {
  /** @var Tests\TestCase $this */
  //@ Set owner to guest
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $owner->is_guest = true;
  $owner->save();
  Event::fake();

  //@ Service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('saveNotCompleteResult');
  });

  $helper = app(RaceHelperService::class);
  $helper->abortRace($roomId, $owner);

  //@ Redis assertions
  $this->assertNotContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
  $this->assertEquals('abort', Redis::HGET("room:$roomId:race:player:$owner->id:progress", 'status'));

  //@ Event assertions
  Event::assertDispatched(
    AbortRace::class,
    fn(AbortRace $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $owner->id
  );
  Event::assertNotDispatched(RaceFinished::class);
});

test('Should abort race & reset all', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));
  Event::fake();

  //@ Service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $player) {
    $mock->shouldReceive('saveNotCompleteResult')->once()->with($roomId, $player, 0);
    $mock->shouldReceive('resetAll')->once()->with($roomId);
  });

  //@ Owner won
  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $owner, 90.0, 90.0, now()->addSeconds(20)->timestamp);

  $helper->abortRace($roomId, $player);

  //@ Redis assertions
  $this->assertEmpty(Redis::SMEMBERS("room:$roomId:race:player"));

  //@ Event assertions
  Event::assertDispatched(
    AbortRace::class,
    fn(AbortRace $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $player->id
  );
  Event::assertDispatched(
    RaceFinished::class,
    fn(RaceFinished $event) =>
    $event->roomId === $roomId
  );
});

test('Should reset all', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');

  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldAllowMockingProtectedMethods()->shouldReceive('removeRacePlayerSetAndProgress')->once()->with($roomId);
  });

  $helper = app(RaceHelperService::class);
  $helper->resetAll($roomId);

  //@ Redis assertions
  $this->assertEquals(0, Redis::EXISTS("room:$roomId:race"));
  $this->assertEquals(0, Redis::EXISTS("room:$roomId:race:place"));
});

test('Should save 1st place user result', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $finishTime = now()->addSeconds(20)->timestamp;

  //@ Set completedCharacters and wrongCharacters
  Redis::HMSET("room:$roomId:race:player:$owner->id:progress", [
    'completedCharacters' => 9,
    'wrongCharacters' => 1
  ]);

  //@ Service assertion
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $owner) {
    $mock->shouldReceive('updateRedisPlayerStats')->once()->with($roomId, $owner->id, 90, '1st', 2);
  });

  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $owner, 90.0, 90.0, $finishTime);

  //@ Redis assertions
  $expectedOwnerProgress = [
    'playerId' => (string) $owner->id,
    'percentage' => '100',
    'wordsPerMinute' => '90',
    'finished' => '1',
    'place' => '1st',
    'status' => 'completed',
    'completedCharacters' => '9',
    'wrongCharacters' => '1'
  ];

  $this->assertContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:place"));
  $this->assertEquals(0, Redis::EXISTS("user:$owner->id:profile"));
  $this->assertEquals($expectedOwnerProgress, Redis::HGETALL("room:$roomId:race:player:$owner->id:progress"));
  $this->assertEquals($finishTime, (int) Redis::HGET("room:$roomId:race", 'finishTime'));

  //@ Database assertions
  assertDatabaseHas('race_results', [
    'user_id' => $owner->id,
    'wpm' => 90,
    'place' => '1st',
    'total_players' => 2,
    'accuracy_percentage' => 90
  ]);
});

test('Should save 2nd place guest result', function () {
  /** @var Tests\TestCase $this */
  //@ Set owner to guest
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));
  $owner->is_guest = true;
  $owner->save();
  $finishTime = now()->addSeconds(20)->timestamp;

  //@ Set completedCharacters and wrongCharacters
  Redis::HMSET("room:$roomId:race:player:$owner->id:progress", [
    'completedCharacters' => 9,
    'wrongCharacters' => 1
  ]);

  //@ Service assertion
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $owner) {
    $mock->shouldReceive('updateRedisPlayerStats')->once()->with($roomId, $owner->id, 90, '2nd', 2);
  });

  //@ Player won
  $helper = app(RaceHelperService::class);
  $helper->saveResult($roomId, $player, 0, 0, $finishTime);
  $helper->saveResult($roomId, $owner, 90.0, 90.0, $finishTime);

  //@ Redis assertions
  $expectedOwnerProgress = [
    'playerId' => (string) $owner->id,
    'percentage' => '100',
    'wordsPerMinute' => '90',
    'finished' => '1',
    'place' => '2nd',
    'status' => 'completed',
    'completedCharacters' => '9',
    'wrongCharacters' => '1'
  ];

  $this->assertContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:place"));
  $this->assertEquals(0, Redis::EXISTS("user:$owner->id:profile"));
  $this->assertEquals($expectedOwnerProgress, Redis::HGETALL("room:$roomId:race:player:$owner->id:progress"));
  $this->assertEquals($finishTime, (int) Redis::HGET("room:$roomId:race", 'finishTime'));

  //@ Database assertions
  assertDatabaseMissing('race_results', ['user_id' => $owner->id]);
});

test('Should save user not complete result', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));

  //@ Service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $owner) {
    $mock->shouldReceive('updateRedisPlayerStats')->once()->with($roomId, $owner->id, 0.0, 'NC', 2);
    $mock->shouldReceive('saveNotCompleteResultToDatabase')->once();
  });

  $helper = app(RaceHelperService::class);
  $helper->saveNotCompleteResult($roomId, $owner, 90.0);

  //@ Redis assertions
  $this->assertEquals(0, Redis::EXISTS("user:$owner->id:profile"));
});

test('Should save guest not complete result', function () {
  /** @var Tests\TestCase $this */
  //@ Set owner to guest
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $owner->is_guest = true;
  $owner->save();

  //@ Service assertions
  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $owner) {
    $mock->shouldReceive('updateRedisPlayerStats')->once()->with($roomId, $owner->id, 0.0, 'NC', 2);
    $mock->shouldNotReceive('saveResultToDatabase');
  });

  $helper = app(RaceHelperService::class);
  $helper->saveNotCompleteResult($roomId, $owner, 90.0);

  //@ Redis assertions
  $this->assertEquals(0, Redis::EXISTS("user:$owner->id:profile"));
});

test('Should save not complete result to database', function () {
  /** @var Tests\TestCase $this */
  $owner = User::find(session('ownerId'));

  $helper = app(RaceHelperService::class);
  $helper->saveNotCompleteResultToDatabase($owner->id, 1, 2, 90.0);

  //@ Database assertions
  assertDatabaseHas('race_results', [
    'user_id' => $owner->id,
    'quote_id' => 1,
    'wpm' => 0,
    'place' => 'NC',
    'total_players' => 2,
    'accuracy_percentage' => 90.0
  ]);
});

test('Should update Redis owner stats for 1st place', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));

  //@ Add owner id to place set
  $helper = app(RaceHelperService::class);
  Redis::SADD("room:$roomId:race:place", $owner->id);

  $helper->updateRedisPlayerStats($roomId, $owner->id, 90.0, '1st', 2);

  //@ Redis assertions
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$owner->id", 'racesPlayed'));
  $this->assertEquals(90.0, (float) Redis::HGET("room:$roomId:player:$owner->id", 'averageWpm'));
  $this->assertEquals(2, (int) Redis::ZSCORE("room:$roomId:player", $owner->id));
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$owner->id", 'racesWon'));
  $this->assertNotContains((string) $owner->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
});

test('Should update Redis player stats for 2nd place', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $owner = User::find(session('ownerId'));
  $player = User::find(session('playerId'));

  //@ Add owner id and player id to place set
  $helper = app(RaceHelperService::class);
  Redis::SADD("room:$roomId:race:place", [$owner->id, $player->id]);

  $helper->updateRedisPlayerStats($roomId, $player->id, 90.0, '2nd', 2);

  //@ Redis assertions
  $this->assertEquals(1, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed'));
  $this->assertEquals(90.0, (float) Redis::HGET("room:$roomId:player:$player->id", 'averageWpm'));
  $this->assertEquals(1, (int) Redis::ZSCORE("room:$roomId:player", $player->id));
  $this->assertEquals(0, (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon'));
  $this->assertNotContains((string) $player->id, Redis::SMEMBERS("room:$roomId:race:inRacePlayer"));
});

test('Should return in race player ids', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');

  $helper = app(RaceHelperService::class);
  $playerIds = $helper->checkHasRaceInProgress($roomId);

  $expectedPlayerIds = [
    1,
    2
  ];

  $this->assertEquals($expectedPlayerIds, $playerIds);
});

test('Should not return in race player ids but empty array', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');

  //@ Reset all (remove race hash)
  $helper = app(RaceHelperService::class);
  $helper->resetAll($roomId);

  $playerIds = $helper->checkHasRaceInProgress($roomId);

  $this->assertEquals([], $playerIds);
});
