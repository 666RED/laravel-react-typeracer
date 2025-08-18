<?php

use App\Events\Race\RaceNotComplete;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RaceNotComplete event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;

  broadcast(new RaceNotComplete($roomId, $playerId));

  Event::assertDispatched(
    RaceNotComplete::class,
    fn(RaceNotComplete $event) =>
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

  broadcast(new RaceNotComplete($roomId, $playerId));

  Event::assertDispatched(
    RaceNotComplete::class,
    fn(RaceNotComplete $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});