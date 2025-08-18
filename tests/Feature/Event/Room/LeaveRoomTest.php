<?php

use App\Events\Room\LeaveRoom;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch LeaveRoom event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $playerName = 'Player';
  $playerCount = 1;

  broadcast(new LeaveRoom($roomId, $playerId, $playerName, $playerCount));

  Event::assertDispatched(
    LeaveRoom::class,
    fn(LeaveRoom $event) =>
    $event->roomId === $roomId &&
      $event->playerId === $playerId &&
      $event->playerName === $playerName &&
      $event->playerCount === $playerCount
  );
});

test('Should broadcast roomId, playerId, playerName and playerCount to public-rooms and room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $playerId = 1;
  $playerName = 'Player';
  $playerCount = 1;
  $data = [
    'roomId' => $roomId,
    'playerId' => $playerId,
    'playerName' => $playerName,
    'playerCount' => $playerCount
  ];

  broadcast(new LeaveRoom($roomId, $playerId, $playerName, $playerCount));

  Event::assertDispatched(
    LeaveRoom::class,
    fn(LeaveRoom $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === 'public-rooms' &&
      $event->broadcastOn()[1]->name === "private-room.$roomId"
  );
});