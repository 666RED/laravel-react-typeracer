<?php

use App\Http\Middleware\User\UpdateUserLastActive;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should update user last active', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create([
    'last_active' => Carbon::now()->subMinutes(5)
  ]);
  Auth::login($user);

  $beforeLastActive = Carbon::parse($user->last_active);

  $request = Request::create(route('home'));
  $next = fn(Request $req) => response('Next called');
  $middleware = new UpdateUserLastActive();

  $response = $middleware->handle($request, $next);

  $user->refresh();
  $afterLastActive = Carbon::parse($user->last_active);

  $this->assertEquals('Next called', $response->getContent());
  $this->assertGreaterThan($beforeLastActive->timestamp, $afterLastActive->timestamp);
});
