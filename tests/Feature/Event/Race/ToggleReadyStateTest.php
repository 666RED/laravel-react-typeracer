<?php

use App\Events\Race\ToggleReadyState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch ToggleReadyState event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $isReady = true;

  broadcast(new ToggleReadyState($roomId, $playerId, $isReady));

  Event::assertDispatched(
    ToggleReadyState::class,
    fn(ToggleReadyState $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $playerId &&
      $event->isReady === $isReady
  );
});

test('Should broadcast playerId and isReady to room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $isReady = true;
  $data = [
    'playerId' => $playerId,
    'isReady' => $isReady
  ];

  broadcast(new ToggleReadyState($roomId, $playerId, $isReady));

  Event::assertDispatched(
    ToggleReadyState::class,
    fn(ToggleReadyState $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});