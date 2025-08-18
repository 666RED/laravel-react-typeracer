<?php

use App\Models\ProfileImage;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('ProfileImage schema should contain all columns', function () {
  /** @var Tests\TestCase $this */
  $this->assertTrue(
    Schema::hasColumns('profile_images', [
      'id',
      'public_id',
      'url',
      'created_at',
      'updated_at',
    ])
  );
});

test('A profile image is belongs to a user', function () {
  /** @var Tests\TestCase $this */
  $profileImage = ProfileImage::create([
    'public_id' => 'random_public_id',
    'url' => 'random_url'
  ]);

  $user = User::factory()->create([
    'profile_image_id' => $profileImage->id
  ]);

  $this->assertInstanceOf(User::class, $profileImage->user);
});
