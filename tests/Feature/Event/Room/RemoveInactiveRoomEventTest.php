<?php

use App\Events\Room\RemoveInactiveRoomEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RemoveInactiveRoom event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();

  broadcast(new RemoveInactiveRoomEvent($roomId));

  Event::assertDispatched(
    RemoveInactiveRoomEvent::class,
    fn(RemoveInactiveRoomEvent $event) =>
    $event->roomId === $roomId
  );
});

test('Should broadcast roomId to room private channel and public-rooms', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $data = [
    'roomId' => $roomId
  ];

  broadcast(new RemoveInactiveRoomEvent($roomId));

  Event::assertDispatched(
    RemoveInactiveRoomEvent::class,
    fn(RemoveInactiveRoomEvent $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId" &&
      $event->broadcastOn()[1]->name === 'public-rooms'
  );
});