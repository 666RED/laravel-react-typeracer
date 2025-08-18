<?php

use App\Http\Middleware\Race\AbortRace;
use App\Models\User;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery\MockInterface;

use function Pest\Laravel\partialMock;

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

  //@ Toggle player ready state to true & start race
  $raceHelper = app(RaceHelperService::class);
  $raceHelper->toggleReadyState($roomId, $player->id, false);
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

test('Should abort race', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Auth::login($player);
  $request = Request::create(route('home'));

  $middleware = new AbortRace();
  $next = fn(Request $req) => response('Next called');

  partialMock(RaceHelperService::class, function (MockInterface $mock) use ($roomId, $player) {
    $mock->shouldReceive('abortRace')->once()->with($roomId, $player);
  });

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});

test('Should not abort race: not get request', function () {
  /** @var Tests\TestCase $this */

  $player = User::find(session('playerId'));
  Auth::login($player);
  $request = Request::create(route('message.send-message'), 'POST');

  $middleware = new AbortRace();
  $next = fn(Request $req) => response('Next called');

  partialMock(RaceHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('abortRace');
  });

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});

test('Should not abort race: no roomId', function () {
  /** @var Tests\TestCase $this */

  $player = User::find(session('playerId'));
  Auth::login($player);
  $request = Request::create(route('message.send-message'), 'POST');
  session()->remove('roomId');

  $middleware = new AbortRace();
  $next = fn(Request $req) => response('Next called');

  partialMock(RaceHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('abortRace');
  });

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});

test('Should not abort race: no user', function () {
  /** @var Tests\TestCase $this */

  $request = Request::create(route('message.send-message'), 'POST');

  $middleware = new AbortRace();
  $next = fn(Request $req) => response('Next called');

  partialMock(RaceHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('abortRace');
  });

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});

test('Should not abort race: refresh page', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $request = Request::create(route('room.show-race', ['roomId' => $roomId]));

  $middleware = new AbortRace();
  $next = fn(Request $req) => response('Next called');

  partialMock(RaceHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('abortRace');
  });

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});
