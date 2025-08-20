<?php

use App\Models\ProfileImage;
use App\Models\RaceResult;
use App\Models\User;
use Database\Seeders\QuoteSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

test('User schema should contain all columns', function () {
  /** @var Tests\TestCase $this */
  $this->assertTrue(
    Schema::hasColumns('users', [
      'id',
      'name',
      'email',
      'email_verified_at',
      'password',
      'remember_token',
      'created_at',
      'updated_at',
      'is_guest',
      'profile_image_id',
      'last_active',
      'room_id',
    ])
  );
});

test('A user has a profile image', function () {
  /** @var Tests\TestCase $this */
  $profileImage = ProfileImage::create([
    'public_id' => 'random_public_id',
    'url' => 'random_url'
  ]);

  $user = User::factory()->create([
    'profile_image_id' => $profileImage->id
  ]);

  $this->assertInstanceOf(ProfileImage::class, $user->profileImage);
  $this->assertEquals(1, $user->profileImage->count());
});

test('A user has one or many results', function () {
  /** @var Tests\TestCase $this */
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  $user = User::factory()->create();
  $results = RaceResult::factory(10)->create([
    'user_id' => $user->id
  ]);

  foreach ($results as $result) {
    $this->assertTrue($user->results->contains($result));
  }
  $this->assertEquals(10, $user->results->count());
  $this->assertInstanceOf(Collection::class, $user->results);
});
