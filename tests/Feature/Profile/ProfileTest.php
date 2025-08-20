<?php

use App\Jobs\UploadImageToCloudinary;
use App\Models\ProfileImage;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\CloudinaryHelperService;
use Carbon\Carbon;
use Database\Seeders\QuoteSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseEmpty;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\partialMock;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
});

test('Should show user profile: without result', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $expectedProfile = [
    'id' => $user->id,
    'name' => $user->name,
    'profileImageUrl' => $user->profileImage?->url,
    'createdAt' => Carbon::parse($user->created_at)->format('Y-m-d'),
    'averageWpm' => 0,
    'averageWpmForLastTenRaces' => 0,
    'accuracyPercentage' => 0,
    'totalRaces' => 0,
    'wonRaces' => 0,
    'notCompletedRaces' => 0,
    'winRate' => 0,
    'bestWpm' => 0,
    'worstWpm' => 0,
  ];

  $this->get(route('profile.show', ['userId' => $user->id]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('profile/index')
        ->has(
          'profileInfo',
          fn(Assert $profile) => $profile
            ->where('id', $expectedProfile['id'])
            ->where('name', $expectedProfile['name'])
            ->where('profileImageUrl', $expectedProfile['profileImageUrl'])
            ->where('createdAt', $expectedProfile['createdAt'])
            ->where('averageWpm', $expectedProfile['averageWpm'])
            ->where('averageWpmForLastTenRaces', $expectedProfile['averageWpmForLastTenRaces'])
            ->where('accuracyPercentage', $expectedProfile['accuracyPercentage'])
            ->where('totalRaces', $expectedProfile['totalRaces'])
            ->where('wonRaces', $expectedProfile['wonRaces'])
            ->where('notCompletedRaces', $expectedProfile['notCompletedRaces'])
            ->where('winRate', $expectedProfile['winRate'])
            ->where('bestWpm', $expectedProfile['bestWpm'])
            ->where('worstWpm', $expectedProfile['worstWpm'])
            ->where('lastActiveAt', 'Currently online'),
        )
    );

  $this->assertEquals(true, Cache::has("user:$user->id:profile"));

  $this->assertEquals($expectedProfile, Cache::get("user:$user->id:profile"));
});

test('Should show user profile: with 11 results', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  RaceResult::factory(11)->create([
    'user_id' => $user->id
  ]);

  $results = $user->results()->select(['quote_id', 'place', 'wpm', 'total_players', 'accuracy_percentage'])->orderBy('created_at', 'desc')->get();
  $totalRaces = $results->count();
  $lastTenRaces = $results->take(10);

  $averageWpm = round($results->average('wpm'), 2);
  $averageWpmForLastTenRaces = round($lastTenRaces->average('wpm'), 2);
  $accuracyPercentage = round($results->average('accuracy_percentage'), 2);
  $wonRaces = $results->filter(fn($result) => $result->place === '1st')->count();
  $notCompletedRaces = $results->filter(fn($result) => $result->place === 'NC')->count();
  // $winRate = round($wonRaces / $totalRaces * 100, 2);
  $bestWpm = $results->max('wpm');
  $worstWpm = $results->filter(fn($result) => $result->place !== 'NC')->min('wpm');

  $expectedProfile = [
    'id' => $user->id,
    'name' => $user->name,
    'profileImageUrl' => $user->profileImage?->url,
    'createdAt' => Carbon::parse($user->created_at)->format('Y-m-d'),
    'averageWpm' => $averageWpm,
    'averageWpmForLastTenRaces' => $averageWpmForLastTenRaces,
    'accuracyPercentage' => $accuracyPercentage,
    'totalRaces' => $totalRaces,
    'wonRaces' => $wonRaces,
    'notCompletedRaces' => $notCompletedRaces,
    // 'winRate' => $winRate,
    'bestWpm' => $bestWpm,
    'worstWpm' => $worstWpm,
  ];

  $this->get(route('profile.show', ['userId' => $user->id]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('profile/index')
        ->has(
          'profileInfo',
          fn(Assert $profile) => $profile
            ->where('id', $user->id)
            ->where('name', $user->name)
            ->where('profileImageUrl', $user->profileImage?->url)
            ->where('createdAt', Carbon::parse($user->created_at)->format('Y-m-d'))
            ->where('averageWpm', $averageWpm)
            ->where('averageWpmForLastTenRaces', $averageWpmForLastTenRaces)
            ->where('accuracyPercentage', $accuracyPercentage)
            ->where('totalRaces', $totalRaces)
            ->where('wonRaces', $wonRaces)
            ->where('notCompletedRaces', $notCompletedRaces)
            ->where('bestWpm', $bestWpm)
            ->where('worstWpm', $worstWpm)
            ->where('lastActiveAt', 'Currently online')
            ->etc()
        )
    );

  $this->assertEquals(true, Cache::has("user:$user->id:profile"));

  $this->assertEquals($expectedProfile, array_filter(Cache::get("user:$user->id:profile"), fn($key) => $key !== 'winRate', ARRAY_FILTER_USE_KEY));
});

test('Should not show user profile: user not found', function () {
  /** @var Tests\TestCase $this */
  $this
    ->get(route('profile.show', ['userId' => 1]))
    ->assertInvalid(['userId' => 'The selected user id is invalid.']);
});

test('Should not show user profile: invalid user id', function () {
  /** @var Tests\TestCase $this */
  $this
    ->get(route('profile.show', ['userId' => -1]))
    ->assertInvalid(['userId' => 'The selected user id is invalid.']);
});

test('Should update user name and profile image: without previous profile image', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $updatedName = 'Updated name';
  $fakeImage = UploadedFile::fake()->image('avatar.jpg');
  $folderName = 'profile-images';
  $path = $fakeImage->store($folderName);
  $fullPath = storage_path("app/private/$path");

  //@ Mock cloudinary upload function (so wont take time)
  partialMock(CloudinaryHelperService::class, function (MockInterface $mock) use ($fullPath, $folderName) {
    $mock->shouldReceive('upload')->once()->with(
      $fullPath,
      $folderName
    )->andReturns([
      'publicId' => 'random_public_id',
      'url' => 'random_url'
    ]);
  });

  $this->actingAs($user)
    ->post(route('profile.update'), [
      'name' => $updatedName,
      'profileImage' => $fakeImage
    ]);

  $user->refresh();
  $image = $user->profileImage;

  //@ Assert database
  assertDatabaseCount('profile_images', 1);
  assertDatabaseHas('users', ['id' => $user->id, 'profile_image_id' => $image->id, 'name' => $updatedName]);

  //@ Assert Cache
  $key = "user:$user->id:profile";

  Cache::has($key);
});

test('Should update user profile & delete previous profile image', function () {
  /** @var Tests\TestCase $this */
  //@ Set previous profile image
  $previousProfileImage = ProfileImage::create([
    'public_id' => 'old_public_id',
    'url' => 'old_url'
  ]);

  $user = User::factory()->create([
    'profile_image_id' => $previousProfileImage->id
  ]);

  assertDatabaseCount('profile_images', 1);
  assertDatabaseHas('users', ['id' => $user->id, 'profile_image_id' => $previousProfileImage->id, 'name' => $user->name]);

  //@ mock cloudinary upload & destroy functions
  $fakeImage = UploadedFile::fake()->image('avatar.jpg');
  $folderName = 'profile-images';
  $path = $fakeImage->store($folderName);
  $fullPath = storage_path("app/private/$path");

  partialMock(CloudinaryHelperService::class, function (MockInterface $mock) use ($fullPath, $folderName) {
    $mock->shouldReceive('upload')->once()->with(
      $fullPath,
      $folderName
    )->andReturns([
      'publicId' => 'random_public_id',
      'url' => 'random_url'
    ]);

    $mock->shouldReceive('destroy')->once()->with(
      'old_public_id'
    );
  });

  //@ Update profile
  $updatedName = 'Updated name';

  $this->actingAs($user)->post(route('profile.update'), [
    'name' => $updatedName,
    'profileImage' => $fakeImage
  ]);

  $user->refresh();
  $currentImage = $user->profileImage;

  //@ Assert database
  assertDatabaseCount('profile_images', 1);
  assertDatabaseHas('users', ['id' => $user->id, 'profile_image_id' => $currentImage->id, 'name' => $updatedName]);

  //@ Assert Cache
  $key = "user:$user->id:profile";

  Cache::has($key);
});

test('Should update user name only', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $updatedName = 'Updated name';

  $this->actingAs($user)->post(route('profile.update'), [
    'name' => $updatedName,
  ]);

  $user->refresh();

  //@ Assert database
  assertDatabaseHas('users', ['id' => $user->id, 'name' => $updatedName, 'profile_image_id' => null]);

  //@ Assert Cache
  $key = "user:$user->id:profile";
  $profileInfo = Cache::get($key);

  Cache::has($key);
  $this->assertEquals($profileInfo['name'], $updatedName);
});

test('Should update user profile image only', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $fakeImage = UploadedFile::fake()->image('avatar.jpg');
  $folderName = 'profile-images';
  $path = $fakeImage->store($folderName);
  $fullPath = storage_path("app/private/$path");

  partialMock(CloudinaryHelperService::class, function (MockInterface $mock) use ($fullPath, $folderName) {
    $mock->shouldReceive('upload')->once()->with(
      $fullPath,
      $folderName
    )->andReturns([
      'publicId' => 'random_public_id',
      'url' => 'random_url'
    ]);
  });

  $this->actingAs($user)->post(route('profile.update'), [
    'profileImage' => $fakeImage
  ]);

  $user->refresh();
  $image = $user->profileImage;

  //@ Assert database
  assertDatabaseCount('profile_images', 1);
  assertDatabaseHas('users', ['id' => $user->id, 'profile_image_id' => $image->id, 'name' => $user->name]);

  //@ Assert Cache
  $key = "user:$user->id:profile";

  Cache::has($key);
});

test('Should not update user profile: missing name and profileImage', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $this->actingAs($user)->post(route('profile.update'), [])
    ->assertInvalid(['name', 'profileImage']);
});

test('Should dispatch UploadImageToCloudinary job', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  $fakeImage = UploadedFile::fake()->image('avatar.jpg');
  $folderName = 'profile-images';
  $path = $fakeImage->store($folderName);
  $fullPath = storage_path("app/private/$path");

  Queue::fake();

  $this->actingAs($user)->post(route('profile.update'), [
    'name' => 'See Hong Chen',
    'profileImage' => $fakeImage
  ]);

  Queue::assertPushed(
    UploadImageToCloudinary::class,
    fn($job)  => $job->getFullPath() === $fullPath && $job->getUserId() === $user->id && $job->getFolderName() === $folderName
  );
});

test('Should delete user profile image', function () {
  /** @var Tests\TestCase $this */
  //@ Set previous profile image
  $previousProfileImage = ProfileImage::create([
    'public_id' => 'old_public_id',
    'url' => 'old_url'
  ]);

  $user = User::factory()->create([
    'profile_image_id' => $previousProfileImage->id
  ]);

  partialMock(CloudinaryHelperService::class, function (MockInterface $mock) {
    $mock->shouldReceive('destroy')->once()->with(
      'old_public_id'
    );
  });

  assertDatabaseCount('profile_images', 1);
  assertDatabaseHas('users', ['id' => $user->id, 'profile_image_id' => $previousProfileImage->id]);

  //@ Delete previous image
  $this->actingAs($user)->followingRedirects()
    ->delete(route('profile.destroy'))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'Profile image deleted')
            ->where('type', 'success')
        )
    );

  $user->refresh();

  $this->assertEquals(null, $user->profile_image_id);
  assertDatabaseEmpty('profile_images');
});

test('Should not delete user profile image', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $this->actingAs($user)->followingRedirects()
    ->delete(route('profile.destroy'))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'flash',
          fn(Assert $flash) => $flash
            ->where('message', 'No profile image')
            ->where('type', 'error')
        )
    );

  $this->assertEquals(null, $user->profile_image_id);
  assertDatabaseEmpty('profile_images');
});

test('Should show first 10 race results', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  RaceResult::factory(20)->create([
    'user_id' => $user->id
  ]);

  $this->get(route('profile.show-results', ['userId' => $user->id]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'results',
          fn(Assert $results) => $results
            ->where('current_page', 1)
            ->has(
              'data',
              10,
              fn(Assert $result) => $result
                ->has('id')
                ->where('user_id', $user->id)
                ->has('quote_id')
                ->has('wpm')
                ->has('place')
                ->has('total_players')
                ->has('created_at')
                ->has('updated_at')
                ->has('accuracy_percentage')
                ->has('quote.id')
                ->has('quote.text')
            )
            ->where('first_page_url', fn($url) => str_contains($url, 'page=1'))
            ->where('from', 1)
            ->where('last_page', 2)
            ->where('last_page_url', fn($url) => str_contains($url, 'page=2'))
            ->has('links', 4)
            ->where('next_page_url', fn($url) => str_contains($url, 'page=2'))
            ->where('path', fn($url) => str_contains($url, "$user->id/results"))
            ->where('per_page', 10)
            ->where('prev_page_url', null)
            ->where('to', 10)
            ->where('total', 20)
        )
    );
});

test('Should show 11-19 race results', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  RaceResult::factory(19)->create([
    'user_id' => $user->id
  ]);

  $this->get(route('profile.show-results', ['userId' => $user->id, 'page' => 2]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'results',
          fn(Assert $results) => $results
            ->where('current_page', 2)
            ->has(
              'data',
              9,
              fn(Assert $result) => $result
                ->has('id')
                ->where('user_id', $user->id)
                ->has('quote_id')
                ->has('wpm')
                ->has('place')
                ->has('total_players')
                ->has('created_at')
                ->has('updated_at')
                ->has('accuracy_percentage')
                ->has('quote.id')
                ->has('quote.text')
            )
            ->where('first_page_url', fn($url) => str_contains($url, 'page=1'))
            ->where('from', 11)
            ->where('last_page', 2)
            ->where('last_page_url', fn($url) => str_contains($url, 'page=2'))
            ->has('links', 4)
            ->where('next_page_url', null)
            ->where('path', fn($url) => str_contains($url, "$user->id/results"))
            ->where('per_page', 10)
            ->where('prev_page_url', fn($url) => str_contains($url, 'page=1'))
            ->where('to', 19)
            ->where('total', 19)
        )
    );
});

test('Should not show race results: invalid user id', function () {
  /** @var Tests\TestCase $this */

  $this->get(route('profile.show-results', ['userId' => -1]))
    ->assertInvalid(
      ['userId' => 'The selected user id is invalid.']
    );
});

test('Should not show race results: user not found', function () {
  /** @var Tests\TestCase $this */
  $this
    ->get(route('profile.show-results', ['userId' => 1]))
    ->assertInvalid(['userId' => 'The selected user id is invalid.']);
});

test('Should not show race results: page not available', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  RaceResult::factory(20)->create([
    'user_id' => $user->id
  ]);

  $this->get(route('profile.show-results', ['userId' => $user->id, 'page' => 999]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'results',
          fn(Assert $results) => $results
            ->where('current_page', 999)
            ->has('data', 0)
            ->where('first_page_url', fn($url) => str_contains($url, 'page=1'))
            ->where('from', null)
            ->where('last_page', 2)
            ->where('last_page_url', fn($url) => str_contains($url, 'page=2'))
            ->has('links', 4)
            ->where('next_page_url', null)
            ->where('path', fn($url) => str_contains($url, "$user->id/results"))
            ->where('per_page', 10)
            ->where('prev_page_url', fn($url) => str_contains($url, "$user->id/results?page=998"))
            ->where('to', null)
            ->where('total', 20)
        )
    );
});

test('Should show first 10 race results: invalid page', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();

  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  RaceResult::factory(20)->create([
    'user_id' => $user->id
  ]);

  $this->get(route('profile.show-results', ['userId' => $user->id, 'page' => -1]))
    ->assertInertia(
      fn(Assert $page) => $page
        ->has(
          'results',
          fn(Assert $results) => $results
            ->where('current_page', 1)
            ->has(
              'data',
              10,
              fn(Assert $result) => $result
                ->has('id')
                ->where('user_id', $user->id)
                ->has('quote_id')
                ->has('wpm')
                ->has('place')
                ->has('total_players')
                ->has('created_at')
                ->has('updated_at')
                ->has('accuracy_percentage')
                ->has('quote.id')
                ->has('quote.text')
            )
            ->where('first_page_url', fn($url) => str_contains($url, 'page=1'))
            ->where('from', 1)
            ->where('last_page', 2)
            ->where('last_page_url', fn($url) => str_contains($url, 'page=2'))
            ->has('links', 4)
            ->where('next_page_url', fn($url) => str_contains($url, 'page=2'))
            ->where('path', fn($url) => str_contains($url, "$user->id/results"))
            ->where('per_page', 10)
            ->where('prev_page_url', null)
            ->where('to', 10)
            ->where('total', 20)
        )
    );
});