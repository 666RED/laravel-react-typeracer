<?php

namespace App\Services;

use App\Events\Room\DeleteRoom;
use App\Events\Room\JoinRoom;
use App\Events\Room\LeaveRoom;
use App\Events\Room\NewRoomCreated;
use App\Events\Room\TransferOwnership;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RoomHelperService
{
  public function createRoom(string $roomId, $room, User $user)
  {
    Redis::HMSET("room:$roomId", $room); // create new room
    $this->addPlayer($roomId, $user->id, $user->name);
    Redis::SADD("available-rooms", $roomId);
    if ($room['private'] !== '1') {
      $room['playerCount'] = 1;
      broadcast(new NewRoomCreated($room)); // Add new room to lobby
    }
    return true;
  }

  public function removeRoom(string $roomId)
  {
    $cursor = 0;
    $pattern = "room:$roomId:*";

    do {
      [$cursor, $keys] = Redis::SCAN($cursor, 'MATCH', $pattern); // ? might add COUNT of 1000

      if (!empty($keys)) {
        Redis::DEL(...$keys);
      }
    } while ($cursor != 0);

    Redis::DEL("room:$roomId");
    Redis::SREM("available-rooms", $roomId);
  }

  public function joinRoom(string $roomId, int $userId, string $userName)
  {
    $isMember = Redis::ZSCORE("room:$roomId:player", $userId);
    // ? User join new room
    if ($isMember === null) {
      $maxPlayer = (int) Redis::HGET("room:$roomId", 'maxPlayer');
      $playerCount = (int) Redis::HGET("room:$roomId", 'playerCount');

      if ($playerCount >= $maxPlayer) {
        return false; // room is full
      } else {
        $this->addPlayer($roomId, $userId, $userName);

        $player = $this->getPlayer($roomId, $userId);

        $playerCount = Redis::HGET("room:$roomId", 'playerCount');

        broadcast(new JoinRoom($roomId, [...$player, 'score' => 0], $player['id'], $playerCount));

        return true;
      }
    } else {
      return true;
    }
  }

  public function transferOwnership(string $roomId)
  {
    // ? Get random player in the room
    $members = Redis::zrange("room:$roomId:player", 0, -1);
    $newOwner = (int) $members[array_rand($members)];
    $newOwnerName = User::find($newOwner)->name;

    // ? Set new owner to room hash
    Redis::HSET("room:$roomId", 'owner', $newOwner);
    Redis::SREM("room:$roomId:race:player", $newOwner); // if the player is in ready state

    $messageHelper = app(MessageHelperService::class);
    $messageHelper->pushMessage($roomId, ['text' => "$newOwnerName is the room owner now", 'senderId' => $newOwner, 'senderName' => $newOwnerName, 'isNotification' => true]);

    broadcast(new TransferOwnership($roomId, $newOwner, $newOwnerName));
  }

  public function leaveRoom(string $roomId, int $playerId, string $playerName)
  {
    $playerCount = Redis::HGET("room:$roomId", 'playerCount');

    broadcast(new LeaveRoom($roomId, $playerId, $playerName, $playerCount));
  }

  public function removePlayer(string $roomId, int $playerId)
  {
    Redis::pipeline(
      function ($pipe) use ($roomId, $playerId) {
        $pipe->HINCRBY("room:$roomId", 'playerCount', -1);
        $pipe->ZREM("room:$roomId:player", $playerId);
      }
    );

    //@ Check if room has no player
    return $this->deleteRoomIfNoPlayerInTheRoom($roomId);
  }


  public function deleteRoomIfNoPlayerInTheRoom(string $roomId)
  {
    $playerCount = Redis::ZCARD("room:$roomId:player");

    if ($playerCount <= 0) {
      $owner = (int) Redis::HGET("room:$roomId", 'owner');
      $this->removeRoom($roomId);

      broadcast(new DeleteRoom($roomId, $owner));

      return true;
    } else {
      return false;
    }
  }

  public function addPlayer(string $roomId, int $userId, string $userName)
  {
    Redis::pipeline(function ($pipe) use ($roomId, $userId, $userName) {
      $pipe->HINCRBY("room:$roomId", 'playerCount', 1);
      $pipe->ZADD("room:$roomId:player", 0, $userId);
      $pipe->HMSET("room:$roomId:player:$userId", ['id' => $userId, 'name' => $userName, 'racesPlayed' => 0, 'averageWpm' => 0, 'racesWon' => 0]);
    });
  }

  public function getPlayer(string $roomId, int $playerId)
  {
    $rawPlayer = Redis::HGETALL("room:$roomId:player:$playerId");
    $player = [];
    foreach ($rawPlayer as $key => $value) {
      $player[$key] = ($key === 'name') ? $value : (int) $value;
    }

    return $player;
  }
}
