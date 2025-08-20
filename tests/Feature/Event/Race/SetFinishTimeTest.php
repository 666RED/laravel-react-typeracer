<?php

use App\Events\Race\SetFinishTime;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch SetFinishTime event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $finishTime = now()->addSeconds(20)->timestamp;

  broadcast(new SetFinishTime($roomId, $finishTime));

  Event::assertDispatched(
    SetFinishTime::class,
    fn(SetFinishTime $event) =>
    $event->roomId === $roomId &&
      $event->finishTime === $finishTime
  );
});

test('Should broadcast finishTime to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $finishTime = now()->addSeconds(20)->timestamp;
  $data = [
    'finishTime' => $finishTime
  ];

  broadcast(new SetFinishTime($roomId, $finishTime));

  Event::assertDispatched(
    SetFinishTime::class,
    fn(SetFinishTime $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});