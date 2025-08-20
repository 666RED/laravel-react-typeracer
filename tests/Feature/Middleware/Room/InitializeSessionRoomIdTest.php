<?php

use App\Http\Middleware\Room\InitializeSessionRoomId;
use App\Models\User;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    'private' => '0'
  ];

  $roomHelper = app(RoomHelperService::class);
  $roomHelper->createRoom($roomId, $room, $owner);
  $roomHelper->addPlayer($roomId, $player->id, $player->name);
  $owner->room_id = $roomId;
  $owner->save();
  $player->room_id = $roomId;
  $player->save();

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
  session()->put('playerId', $player->id);
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should pass the middleware if session has roomId', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('home'));

  $middleware = new InitializeSessionRoomId();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});

test('Should set user room_id to session', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('home'));
  $player = User::find(session('playerId'));
  $roomId = $player->room_id;
  Auth::login($player);

  //@ Remove session roomId
  session()->remove('roomId');

  $middleware = new InitializeSessionRoomId();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
  $this->assertEquals($roomId, session('roomId'));
});

test('Should pass the middleware if session does not have roomId and user is not logged in', function () {
  /** @var Tests\TestCase $this */
  $request = Request::create(route('home'));

  //@ Remove session roomId
  session()->remove('roomId');

  $middleware = new InitializeSessionRoomId();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
  $this->assertNull(session('roomId'));
});
