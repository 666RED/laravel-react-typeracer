<?php

use App\Events\Room\DeleteRoom;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch DeleteRoom event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $owner = 1;

  broadcast(new DeleteRoom($roomId, $owner));

  Event::assertDispatched(
    DeleteRoom::class,
    fn(DeleteRoom $event) =>
    $event->roomId === $roomId &&
      $event->owner === $owner
  );
});

test('Should broadcast owner and room id to room private channel and public-rooms ', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $owner = 1;
  $data = ['owner' => $owner, 'id' => $roomId];

  broadcast(new DeleteRoom($roomId, $owner));

  Event::assertDispatched(
    DeleteRoom::class,
    fn(DeleteRoom $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId" &&
      $event->broadcastOn()[1]->name === "public-rooms"
  );
});