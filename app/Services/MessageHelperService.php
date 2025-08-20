<?php

namespace App\Services;

use App\Events\Message\MessageSent;
use Illuminate\Support\Facades\Redis;

class MessageHelperService
{
  public function pushMessage(string $roomId, $message)
  {
    $text = $message['text'];
    $senderId = $message['senderId'];
    $senderName = $message['senderName'];
    $isNotification = $message['isNotification'];

    $messageId = Redis::INCR("room:$roomId:message:id");

    $message = [
      'id' => $messageId,
      'senderId' => $senderId,
      'senderName' => $senderName,
      'text' => $text,
      'isNotification' => $isNotification
      // 'timestamp' => now()->toISOString() // ? may add this
    ];

    Redis::RPUSH("room:$roomId:message", json_encode($message));

    broadcast(new MessageSent($roomId, $message));
  }

  public function getMessages($roomId)
  {
    return array_map(fn($json) => (array) json_decode($json), Redis::LRANGE("room:$roomId:message", -20, -1)); // latest 20 messages
  }
}
