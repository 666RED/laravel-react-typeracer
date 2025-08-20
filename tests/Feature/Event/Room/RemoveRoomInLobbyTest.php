<?php

use App\Events\Room\RemoveRoomInLobby;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RemoveRoomInLobby event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();

  broadcast(new RemoveRoomInLobby($roomId));

  Event::assertDispatched(
    RemoveRoomInLobby::class,
    fn(RemoveRoomInLobby $event) =>
    $event->roomId === $roomId
  );
});

test('Should broadcast roomId to public-rooms', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $data = [
    'id' => $roomId
  ];

  broadcast(new RemoveRoomInLobby($roomId));

  Event::assertDispatched(
    RemoveRoomInLobby::class,
    fn(RemoveRoomInLobby $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === 'public-rooms'
  );
});