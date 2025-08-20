<?php

use App\Events\Race\RaceReady;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RaceReady event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerIds = [1, 2, 3];

  broadcast(new RaceReady($roomId, $playerIds));

  Event::assertDispatched(
    RaceReady::class,
    fn(RaceReady $event) =>
    $event->roomId === $roomId &&
      $event->playerIds === $playerIds
  );
});

test('Should broadcast playerIds to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerIds = [1, 2, 3];
  $data = [
    'playerIds' => $playerIds
  ];

  broadcast(new RaceReady($roomId, $playerIds));

  Event::assertDispatched(
    RaceReady::class,
    fn(RaceReady $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});