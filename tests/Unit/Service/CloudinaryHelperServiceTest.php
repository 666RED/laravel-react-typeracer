<?php

use App\Helpers\CloudinaryHelper;
use App\Services\CloudinaryHelperService;
use Illuminate\Http\UploadedFile;

test('Should resolve CloudinaryHelper facade', function () {
  /** @var Tests\TestCase $this */
  $instance = CloudinaryHelper::getFacadeRoot();
  $this->assertInstanceOf(CloudinaryHelperService::class, $instance);
});

test('Should upload image to cloudinary & destroy it afterwards', function () {
  /** @var Tests\TestCase $this */
  $fakeImage = UploadedFile::fake()->image('avatar.jpg');
  $folderName = 'profile-images';
  $path = $fakeImage->store($folderName);
  $fullPath = storage_path("app/private/$path");

  $helper = app(CloudinaryHelperService::class);

  $result = $helper->upload($fullPath, $folderName);
  expect($result['publicId'])->not->toBeEmpty();
  expect($result['url'])->toContain('http://res.cloudinary.com/');

  $helper->destroy($result['publicId']);
});
