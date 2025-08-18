<?php

use App\Events\Race\AbortRace;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch AbortRace event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;

  broadcast(new AbortRace($roomId, $playerId));

  Event::assertDispatched(
    AbortRace::class,
    fn(AbortRace $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $playerId
  );
});

test('Should broadcast playerId to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $data = [
    'playerId' => $playerId
  ];

  broadcast(new AbortRace($roomId, $playerId));

  Event::assertDispatched(
    AbortRace::class,
    fn(AbortRace $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});