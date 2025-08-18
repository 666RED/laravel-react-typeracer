<?php

use App\Events\Room\UpdateRoomInLobby;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch UpdateRoomInLobby event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $maxPlayer = 2;
  $name = 'Updated room name';

  broadcast(new UpdateRoomInLobby($roomId, $maxPlayer, $name));

  Event::assertDispatched(
    UpdateRoomInLobby::class,
    fn(UpdateRoomInLobby $event) =>
    $event->roomId === $roomId &&
      $event->maxPlayer === $maxPlayer &&
      $event->name === $name
  );
});

test('Should broadcast updated room to public rooms', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $maxPlayer = 2;
  $name = 'Updated room name';
  $data = [
    'roomId' => $roomId,
    'maxPlayer' => $maxPlayer,
    'name' => $name
  ];

  broadcast(new UpdateRoomInLobby($roomId, $maxPlayer, $name));

  Event::assertDispatched(
    UpdateRoomInLobby::class,
    fn(UpdateRoomInLobby $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "public-rooms"
  );
});