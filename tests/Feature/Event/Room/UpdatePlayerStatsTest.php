<?php

use App\Events\Room\UpdatePlayerStats;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch UpdatePlayerStats event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $userId = 1;
  $score = 5;
  $averageWpm = 80.5;
  $racesPlayed = 1;
  $racesWon = 1;

  broadcast(new UpdatePlayerStats($roomId, $userId, $score, $averageWpm, $racesPlayed, $racesWon));

  Event::assertDispatched(
    UpdatePlayerStats::class,
    fn(UpdatePlayerStats $event) =>
    $event->roomId === $roomId &&
      $event->userId === $userId &&
      $event->score === $score &&
      $event->averageWpm === $averageWpm &&
      $event->racesPlayed === $racesPlayed &&
      $event->racesWon === $racesWon
  );
});

test('Should broadcast user stats to room private channel', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $userId = 1;
  $score = 5;
  $averageWpm = 80.5;
  $racesPlayed = 1;
  $racesWon = 1;
  $data = [
    'id' => $userId,
    'score' => $score,
    'averageWpm' => $averageWpm,
    'racesPlayed' => $racesPlayed,
    'racesWon' => $racesWon
  ];

  broadcast(new UpdatePlayerStats($roomId, $userId, $score, $averageWpm, $racesPlayed, $racesWon));

  Event::assertDispatched(
    UpdatePlayerStats::class,
    fn(UpdatePlayerStats $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});