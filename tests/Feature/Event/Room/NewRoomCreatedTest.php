<?php

use App\Events\Room\NewRoomCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch NewRoomCreated event', function () {
  Event::fake();

  $room = [
    'id' => (string) Str::uuid(),
    'name' => 'New room',
    'owner' => 1,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  broadcast(new NewRoomCreated($room));

  Event::assertDispatched(
    NewRoomCreated::class,
    fn(NewRoomCreated $event) =>
    $event->room === $room
  );
});

test('Should broadcast room to public-rooms', function () {
  Event::fake();

  $room = [
    'id' => (string) Str::uuid(),
    'name' => 'New room',
    'owner' => 1,
    'playerCount' => 0,
    'maxPlayer' => 2,
    'private' => '0'
  ];

  broadcast(new NewRoomCreated($room));

  Event::assertDispatched(
    NewRoomCreated::class,
    fn(NewRoomCreated $event) =>
    $event->broadcastWith() === $room &&
      $event->broadcastOn()[0]->name === 'public-rooms'
  );
});