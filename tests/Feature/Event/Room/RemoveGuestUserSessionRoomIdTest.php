<?php

use App\Events\Room\RemoveGuestUserSessionRoomId;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch RemoveGuestUserSessionRoomId event', function () {
  Event::fake();

  $userId = 1;

  broadcast(new RemoveGuestUserSessionRoomId($userId));

  Event::assertDispatched(
    RemoveGuestUserSessionRoomId::class,
    fn(RemoveGuestUserSessionRoomId $event) =>
    $event->userId === $userId
  );
});

test('Should broadcast userId to user private channel', function () {
  Event::fake();

  $userId = 1;
  $data = [
    'userId' => $userId
  ];

  broadcast(new RemoveGuestUserSessionRoomId($userId));

  Event::assertDispatched(
    RemoveGuestUserSessionRoomId::class,
    fn(RemoveGuestUserSessionRoomId $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-user.$userId"
  );
});