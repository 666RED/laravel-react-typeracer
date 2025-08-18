<?php

use App\Events\Room\JoinRoom;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch JoinRoom event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $player = [
    'id' => 1,
    'name' => 'Player',
    'racesPlayed' => 0,
    'averageWpm' => 0,
    'racesWon' => 0
  ];
  $playerId = $player['id'];
  $playerCount = 1;

  broadcast(new JoinRoom($roomId, $player, $playerId, $playerCount));

  Event::assertDispatched(
    JoinRoom::class,
    fn(JoinRoom $event) =>
    $event->roomId === $roomId &&
      $event->player === $player &&
      $event->playerId === $playerId &&
      $event->playerCount === $playerCount
  );
});

test('Should broadcast roomId, player, playerId and playerCount to public-rooms and room private channel ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $player = [
    'id' => 1,
    'name' => 'Player',
    'racesPlayed' => 0,
    'averageWpm' => 0,
    'racesWon' => 0
  ];
  $playerId = $player['id'];
  $playerCount = 1;
  $data = [
    'roomId' => $roomId,
    'player' => $player,
    'playerId' => $playerId,
    'playerCount' => $playerCount
  ];

  broadcast(new JoinRoom($roomId, $player, $playerId, $playerCount));

  Event::assertDispatched(
    JoinRoom::class,
    fn(JoinRoom $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === 'public-rooms' &&
      $event->broadcastOn()[1]->name === "private-room.$roomId"
  );
});