<?php

use App\Events\Race\UpdateProgress;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch UpdateProgress event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $userId = 1;
  $percentage = 40.6;
  $wordsPerMinute = 80.54;

  broadcast(new UpdateProgress($roomId, $userId, $percentage, $wordsPerMinute));

  Event::assertDispatched(
    UpdateProgress::class,
    fn(UpdateProgress $event) =>
    $event->roomId === $roomId &&
      $event->userId === $userId &&
      $event->percentage === $percentage &&
      $event->wordsPerMinute === $wordsPerMinute
  );
});

test('Should broadcast userId, percentage and wordsPerMinute to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $userId = 1;
  $percentage = 40.2;
  $wordsPerMinute = 80.2;
  $data = [
    'userId' => $userId,
    'percentage' => $percentage,
    'wordsPerMinute' => $wordsPerMinute
  ];

  broadcast(new UpdateProgress($roomId, $userId, $percentage, $wordsPerMinute));

  Event::assertDispatched(
    UpdateProgress::class,
    fn(UpdateProgress $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});