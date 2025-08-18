<?php

use App\Http\Middleware\Room\CheckIfSessionHasRoomId;
use App\Models\User;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

test('Should redirect user to home if roomId not exist in session & user table room_id is null', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Auth::login($player);

  $request = Request::create(route('room.show', ['roomId' => $roomId]));

  //@ Remove session roomId & user table room_id
  session()->remove('roomId');
  $player->room_id = null;
  $player->save();

  $middleware = new CheckIfSessionHasRoomId();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertNull(session('roomId'));
  $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  $this->assertEquals('Join a room first', session('message'));
  $this->assertEquals('warning', session('type'));
  $this->assertNotEquals('Next called', $response->getContent());
});

test('Should not redirect user to home if session has roomId', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $player = User::find(session('playerId'));
  Auth::login($player);

  $request = Request::create(route('room.show', ['roomId' => $roomId]));

  $middleware = new CheckIfSessionHasRoomId();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertNotNull(session('roomId'));
  $this->assertEquals('Next called', $response->getContent());
});
