<?php

use App\Events\Race\RaceFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RaceFinished event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();

  broadcast(new RaceFinished($roomId));

  Event::assertDispatched(
    RaceFinished::class,
    fn(RaceFinished $event) =>
    $event->roomId === $roomId
  );
});

test('Should broadcast nothing to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();

  broadcast(new RaceFinished($roomId));

  Event::assertDispatched(
    RaceFinished::class,
    fn(RaceFinished $event) => $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});