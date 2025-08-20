<?php

use App\Events\Room\TransferOwnership;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch TransferOwnership event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $newOwner = 1;
  $newOwnerName = 'Owner';

  broadcast(new TransferOwnership($roomId, $newOwner, $newOwnerName));

  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) =>
    $event->roomId === $roomId &&
      $event->newOwner === $newOwner &&
      $event->newOwnerName === $newOwnerName
  );
});

test('Should broadcast new owner id and name to private room channel', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $newOwner = 1;
  $newOwnerName = 'Owner';
  $data = [
    'newOwner' => $newOwner,
    'newOwnerName' => $newOwnerName
  ];

  broadcast(new TransferOwnership($roomId, $newOwner, $newOwnerName));

  Event::assertDispatched(
    TransferOwnership::class,
    fn(TransferOwnership $event) =>
    $event->broadcastWith() === $data &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});