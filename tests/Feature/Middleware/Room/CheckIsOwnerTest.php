<?php

use App\Http\Middleware\Room\CheckIsOwner;
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

test('Should show warning message if user is not room owner', function () {
  /** @var Tests\TestCase $this */

  $player = User::find(session('playerId'));
  Auth::login($player);

  $request = Request::create(route('room.delete'), 'delete');

  $middleware = new CheckIsOwner();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  $this->assertEquals('Only room owner can perform this action', session('message'));
  $this->assertEquals('warning', session('type'));
  $this->assertNotEquals('Next called', $response->getContent());
});

test('Should pass the middleware if user is owner', function () {
  /** @var Tests\TestCase $this */

  $owner = User::find(session('ownerId'));
  Auth::login($owner);

  $request = Request::create(route('room.delete'), 'delete');

  $middleware = new CheckIsOwner();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $this->assertEquals('Next called', $response->getContent());
});
