<?php

use App\Events\Message\MessageSent;
use App\Helpers\MessageHelper;
use App\Models\User;
use App\Services\MessageHelperService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
  Redis::flushdb();
});

afterEach(function () {
  Redis::flushdb();
  session()->flush();
});

test('Should resolve MessageHelper facade', function () {
  /** @var Tests\TestCase $this */
  $instance = MessageHelper::getFacadeRoot();
  $this->assertInstanceOf(MessageHelperService::class, $instance);
});

test('Should push message', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();
  $sender = User::factory()->create();
  Event::fake();

  $message = [
    'senderId' => $sender->id,
    'senderName' => $sender->name,
    'text' => 'Hello World',
    'isNotification' => false
  ];

  $helper = app(MessageHelperService::class);
  $helper->pushMessage($roomId, $message);

  $message = ['id' => 1] + $message;

  //@ Redis assertions
  $actualMessages = array_map(fn($json) => (array) json_decode($json), Redis::LRANGE("room:$roomId:message", -20, -1));
  $this->assertContains($message, $actualMessages);

  //@ Event assertions
  Event::assertDispatched(
    MessageSent::class,
    fn(MessageSent $event) =>
    $event->roomId === $roomId &&
      $event->message === $message
  );
});

test('Should get messages', function () {
  /** @var Tests\TestCase $this */
  $roomId = (string) Str::uuid();

  //@ push message 
  $message = [
    'id' => 1,
    'senderId' => 1,
    'senderName' => 'Sender',
    'text' => 'Just text',
    'isNotification' => true
  ];

  Redis::RPUSH("room:$roomId:message", json_encode($message));

  $helper = app(MessageHelperService::class);
  $result = $helper->getMessages($roomId);

  $this->assertContains($message, $result);
});
