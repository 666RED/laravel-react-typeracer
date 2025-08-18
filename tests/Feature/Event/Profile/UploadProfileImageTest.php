<?php

use App\Events\Profile\UploadProfileImage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch UploadProfileImage event', function () {
  Event::fake();

  $userId = 1;

  broadcast(new UploadProfileImage($userId));

  Event::assertDispatched(
    UploadProfileImage::class,
    fn(UploadProfileImage $event) =>
    $event->userId === $userId
  );
});

test('Should broadcast nothing to user private channel', function () {
  Event::fake();

  $userId = 1;

  broadcast(new UploadProfileImage($userId));

  Event::assertDispatched(
    UploadProfileImage::class,
    fn(UploadProfileImage $event) =>
    $event->broadcastOn()[0]->name === "private-user.$userId"
  );
});