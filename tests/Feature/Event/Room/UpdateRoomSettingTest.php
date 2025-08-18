<?php

use App\Events\Room\UpdateRoomSetting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch UpdateRoomSetting event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $owner = 1;

  broadcast(new UpdateRoomSetting($roomId, $owner));

  Event::assertDispatched(
    UpdateRoomSetting::class,
    fn(UpdateRoomSetting $event) =>
    $event->roomId === $roomId &&
      $event->owner === $owner
  );
});

test('Should broadcast owner id to room private channel', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $owner = 1;
  $data = [
    'owner' => $owner
  ];

  broadcast(new UpdateRoomSetting($roomId, $owner));

  Event::assertDispatched(
    UpdateRoomSetting::class,
    fn(UpdateRoomSetting $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});