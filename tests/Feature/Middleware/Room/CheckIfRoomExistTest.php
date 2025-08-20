<?php

use App\Http\Middleware\Room\CheckIfRoomExist;
use App\Models\User;
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

test('Should redirect user to home if room does not exist', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $playerId = session('playerId');

  //@ Remove room
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->removeRoom($roomId);

  $player = User::find($playerId);
  Auth::login($player);

  //@ Helper service assertions
  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldReceive('removeRoom')->once()->with($roomId);
  });

  $request = Request::create(route('room.show', ['roomId' => $roomId]));

  $middleware = new CheckIfRoomExist();
  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $player->refresh();

  $this->assertNull(session('roomId'));
  $this->assertNull($player->room_id);
  $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  $this->assertEquals('Room not found', session('message'));
  $this->assertEquals('warning', session('type'));
  $this->assertNotEquals('Next called', $response->getContent());
});

test('Should redirect user to home if room has expired', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $playerId = session('playerId');

  //@ Remove room & add roomId back to available-rooms set
  $roomHelper = app(RoomHelperService::class);
  $roomHelper->removeRoom($roomId);
  Redis::SADD("available-rooms", $roomId);

  $player = User::find($playerId);
  Auth::login($player);

  //@ Helper service assertions
  partialMock(RoomHelperService::class, function (MockInterface $mock) use ($roomId) {
    $mock->shouldReceive('removeRoom')->once()->with($roomId);
  });

  $request = Request::create(route('room.show', ['roomId' => $roomId]));

  $middleware = new CheckIfRoomExist();
  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $player->refresh();

  $this->assertNull(session('roomId'));
  $this->assertNull($player->room_id);
  $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  $this->assertEquals('Room not found', session('message'));
  $this->assertEquals('warning', session('type'));
  $this->assertNotEquals('Next called', $response->getContent());
});

test('Should not redirect user to home if room exists', function () {
  /** @var Tests\TestCase $this */

  $roomId = session('roomId');
  $playerId = session('playerId');
  $player = User::find($playerId);
  Auth::login($player);

  //@ Helper service assertions
  partialMock(RoomHelperService::class, function (MockInterface $mock) {
    $mock->shouldNotReceive('removeRoom');
  });

  $request = Request::create(route('room.show', ['roomId' => $roomId]));

  $middleware = new CheckIfRoomExist();
  $next = fn(Request $req) => response('Next called');

  $response = $middleware->handle($request, $next);

  $player->refresh();

  $this->assertNotNull(session('roomId'));
  $this->assertNotNull($player->room_id);
  $this->assertEquals('Next called', $response->getContent());
});
