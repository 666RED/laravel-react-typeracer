<?php

use App\Events\Message\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('Should dispatch MessageSent event', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $message = [
    'id' => 1,
    'senderId' => 2,
    'senderName' => 'Sender',
    'text' => 'Hello World',
    'isNotification' => true
  ];

  broadcast(new MessageSent($roomId, $message));

  Event::assertDispatched(
    MessageSent::class,
    fn(MessageSent $event) =>
    $event->roomId === $roomId &&
      $event->message === $message
  );
});

test('Should broadcast message to room private channel', function () {
  Event::fake();

  $roomId = (string) Str::uuid();
  $message = [
    'id' => 1,
    'senderId' => 2,
    'senderName' => 'Sender',
    'text' => 'Hello World',
    'isNotification' => true
  ];

  broadcast(new MessageSent($roomId, $message));

  Event::assertDispatched(
    MessageSent::class,
    fn(MessageSent $event) =>
    $event->broadcastWith() === $message &&
      $event->broadcastOn()[0]->name === "private-room.$roomId"
  );
});