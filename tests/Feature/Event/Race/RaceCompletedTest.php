<?php

use App\Events\Race\RaceCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RaceCompleted event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $wordsPerMinute = 90.4;
  $place = '2nd';

  broadcast(new RaceCompleted($roomId, $playerId, $wordsPerMinute, $place));

  Event::assertDispatched(
    RaceCompleted::class,
    fn(RaceCompleted $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $playerId &&
      $event->wordsPerMinute === $wordsPerMinute &&
      $event->place === $place
  );
});

test('Should broadcast playerId, wordsPerMinute and place to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $wordsPerMinute = 90.4;
  $place = '2nd';
  $data = [
    'playerId' => $playerId,
    'wordsPerMinute' => $wordsPerMinute,
    'place' => $place
  ];

  broadcast(new RaceCompleted($roomId, $playerId, $wordsPerMinute, $place));

  Event::assertDispatched(
    RaceCompleted::class,
    fn(RaceCompleted $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});