<?php

use App\Http\Middleware\Room\UpdateRoomExpirationTime;
use App\Models\User;
use App\Services\RoomHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
  Redis::flushdb();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  //@ Create room & add player
  $roomId = (string) Str::uuid();
  $owner = User::factory()->create();
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

  session()->put('roomId', $roomId);
  session()->put('ownerId', $owner->id);
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should update room expiration time if session has roomId and room exists', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $request = Request::create(route('home'));

  $middleware = new UpdateRoomExpirationTime();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $roomExpirationTime = Redis::EXPIRETIME("room:$roomId");
  $expectedMin = now()->addHours(2)->timestamp - 5; //@ 5 seconds latency
  $expectedMax = now()->addHours(2)->timestamp + 5;

  $this->assertEquals('Next called', $response->getContent());
  $this->assertGreaterThan($expectedMin, $roomExpirationTime);
  $this->assertLessThan($expectedMax, $roomExpirationTime);
});

test('Should not update room expiration time: session does not have roomId', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $request = Request::create(route('home'));

  //@ Remove roomId
  session()->remove("roomId");

  $middleware = new UpdateRoomExpirationTime();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $roomExpirationTime = Redis::EXPIRETIME("room:$roomId");

  $this->assertEquals('Next called', $response->getContent());
  $this->assertEquals(-1, $roomExpirationTime);
});

test('Should not update room expiration time: room does not exist', function () {
  /** @var Tests\TestCase $this */
  $roomId = session('roomId');
  $request = Request::create(route('home'));

  //@ Remove room
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->removeRoom($roomId);

  $middleware = new UpdateRoomExpirationTime();

  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $roomExpirationTime = Redis::EXPIRETIME("room:$roomId");

  $this->assertEquals('Next called', $response->getContent());
  $this->assertEquals(-2, $roomExpirationTime); //@ room:$roomId does not exist
});
