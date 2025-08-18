<?php

use App\Helpers\ProfileHelper;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\ProfileHelperService;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
});

test('Should resolve ProfileHelper facade', function () {
  /** @var Tests\TestCase $this */
  $instance = ProfileHelper::getFacadeRoot();
  $this->assertInstanceOf(ProfileHelperService::class, $instance);
});

test('Should get user profile info', function () {
  /** @var Tests\TestCase $this */
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  $user = User::factory()->create();

  RaceResult::factory(20)->create([
    'user_id' => $user->id
  ]);

  $helper = app(ProfileHelperService::class);
  $profileInfo = $helper->getUserProfileInfoCache($user);

  $this->assertIsInt($profileInfo['id']);
  $this->assertIsString($profileInfo['name']);
  $this->assertTrue($profileInfo['profileImageUrl'] === null || is_string($profileInfo['profileImageUrl']));
  $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $profileInfo['createdAt']);
  $this->assertIsFloat($profileInfo['averageWpm']);
  $this->assertIsFloat($profileInfo['averageWpmForLastTenRaces']);
  $this->assertIsFloat($profileInfo['accuracyPercentage']);
  $this->assertIsInt($profileInfo['totalRaces']);
  $this->assertIsInt($profileInfo['wonRaces']);
  $this->assertIsInt($profileInfo['notCompletedRaces']);
  $this->assertIsFloat($profileInfo['winRate']);
  $this->assertIsFloat($profileInfo['bestWpm']);
  $this->assertIsFloat($profileInfo['worstWpm']);
  $this->assertIsString($profileInfo['lastActiveAt']);
});
