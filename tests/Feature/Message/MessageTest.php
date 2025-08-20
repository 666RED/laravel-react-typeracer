<?php

use App\Events\Message\MessageSent;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
  Redis::flushDb();
  session()->put('roomId', '123');
});

afterEach(function () {
  Redis::flushDb();
});

test('Should send room message', function () {
  /** @var \Tests\TestCase $this */
  Event::fake();

  $user = User::factory()->create();

  $message = [
    'text' => "Hello World",
    'senderId' => $user->id,
    'senderName' => $user->name,
    'isNotification' => false
  ];

  $this->actingAs($user)
    ->post(route('message.send-message'), $message)
    ->assertStatus(200);

  //@ Redis data confirmation
  $roomId = session('roomId');

  $this->assertEquals('1', Redis::GET("room:$roomId:message:id"));

  $expectedMessage = [
    'id' => '1',
    'senderId' => $message['senderId'],
    'senderName' => $message['senderName'],
    'text' => $message['text'],
    'isNotification' => $message['isNotification']
  ];
  $actualMessage = json_decode(Redis::LRANGE("room:$roomId:message", 0, -1)[0], true);
  $this->assertEquals($expectedMessage, $actualMessage);

  Event::assertDispatched(
    MessageSent::class,
    fn($e) =>  $e->message['senderId'] === $message['senderId'] && $e->message['text'] === $message['text']
  );
});

test('Should not send message: all field missing', function () {
  /** @var Tests\TestCase $this */
  Event::fake();

  $user = User::factory()->create();

  $this->actingAs($user)
    ->post(route('message.send-message'), [])
    ->assertInvalid(['text', 'senderId', 'senderName', 'isNotification']);

  Event::assertNotDispatched(MessageSent::class);
});

test('Should not send message: text > 500', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $message = [
    'text' => Str::random(501),
    'senderId' => $user->id,
    'senderName' => $user->name,
    'isNotification' => false
  ];

  $this->actingAs($user)
    ->post(route('message.send-message'), $message)
    ->assertInvalid(['text' => "The text field must not be greater than 500 characters."]);

  Event::assertNotDispatched(MessageSent::class);
});

test('Should not send message: invalid senderId', function () {
  /** @var Tests\TestCase $this */
  $user = User::factory()->create();
  Event::fake();

  $message = [
    'text' => Str::random(100),
    'senderId' => -1,
    'senderName' => 'Random user name',
    'isNotification' => false
  ];

  $this->actingAs($user)
    ->post(route('message.send-message'), $message)
    ->assertInvalid(['senderId' => "The selected sender id is invalid."]);

  Event::assertNotDispatched(MessageSent::class);
});