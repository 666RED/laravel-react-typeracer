<?php

namespace App\Services;

use App\Events\Race\AbortRace;
use App\Events\Race\RaceFinished;
use App\Models\Quote;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class RaceHelperService
{
  public function setRaceReady(string $roomId, int $ownerId,)
  {
    $startTime = now()->addSeconds(10)->timestamp;
    $quote = Quote::select('id')->inRandomOrder()->first();
    $quoteId = $quote->id;
    $playerIds = $this->getPlayerIds($roomId);

    Redis::pipeline(function ($pipe) use ($roomId, $ownerId, $quoteId, $startTime, $playerIds) {
      $pipe->SADD("room:$roomId:race:player", $ownerId);

      $pipe->HMSET("room:$roomId:race", ['startTime' => $startTime]);

      $pipe->HMSET("room:$roomId:race", ['quoteId' => $quoteId]);

      //@ inRacePlayer keep track of player who still not complete the race
      $pipe->SADD("room:$roomId:race:inRacePlayer", ...[...$playerIds, $ownerId]);
    });
  }

  /**
   * @return int[]
   */
  public function getPlayerIds($roomId)
  {
    $playerIds = Redis::SMEMBERS("room:$roomId:race:player");
    return array_map(fn($id) => (int) $id, $playerIds);
  }

  public function getInRacePlayerIds($roomId)
  {
    $playerIds = Redis::SMEMBERS("room:$roomId:race:inRacePlayer");
    return array_map(fn($id) => (int) $id, $playerIds);
  }

  public function getAllPlayers($roomId)
  {
    $playerIds = Redis::SMEMBERS("room:$roomId:race:player");

    return array_map(function ($playerId) use ($roomId) {
      $key = "room:$roomId:race:player:$playerId:progress";

      $user = User::select('name')->find($playerId);
      $id = (int) $playerId;
      $name = $user->name;
      $progress = Redis::HGETALL($key);

      $percentage = (float) $progress['percentage'];
      $wordsPerMinute = (float) $progress['wordsPerMinute'];
      $finished = $progress['finished'] === '1';
      $status = $progress['status'];

      $rawPlace = $progress['place'];
      $place = $rawPlace === '0' ? '' : $rawPlace;

      return ['id' => $id, 'name' => $name, 'percentage' => $percentage, 'wordsPerMinute' => $wordsPerMinute, 'finished' => $finished, 'place' => $place, 'status' => $status];
    }, $playerIds);
  }

  public function getRoomPlayers($roomId)
  {
    $playerIds = Redis::ZRANGE("room:$roomId:player", 0, -1);

    return array_map(function ($playerId) use ($roomId) {
      $rawPlayer = Redis::HGETALL("room:$roomId:player:$playerId");
      $player = [];
      foreach ($rawPlayer as $key => $value) {
        $player[$key] = $key === 'name' ? $value : ($key === 'averageWpm' ? (float) $value : (int) $value);
      }
      $score = (int) Redis::ZSCORE("room:$roomId:player", $playerId);

      $status = !Redis::SISMEMBER("room:$roomId:race:player", $playerId)
        ? 'idle'
        : (!Redis::EXISTS("room:$roomId:race:player:$playerId:progress")
          ? 'ready'
          : Redis::HGET("room:$roomId:race:player:$playerId:progress", 'status'));
      return [...$player, 'score' => $score, 'status' => $status];
    }, $playerIds);
  }

  public function getRacePlace($roomId): string
  {
    $placeArray = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'];
    $placeCount = Redis::SCARD("room:$roomId:race:place");
    return $placeArray[$placeCount];
  }

  public function setRacePlayersInitialState($roomId)
  {
    $playerIds = Redis::SMEMBERS("room:$roomId:race:player");

    array_map(function ($id) use ($roomId) {
      $playerProgress = [
        'playerId' => $id,
        'percentage' => 0,
        'wordsPerMinute' => 0,
        'completedCharacters' => 0,
        'wrongCharacters' => 0,
        'finished' => 0,
        'place' => 0,
        'status' => 'play'
      ];

      Redis::HMSET("room:$roomId:race:player:$id:progress", $playerProgress);
    }, $playerIds);
  }

  public function abortRace(string $roomId, User $user)
  {
    $userId = $user->id;
    Redis::SREM("room:$roomId:race:inRacePlayer", $userId);
    Redis::HSET("room:$roomId:race:player:$userId:progress", 'status', 'abort');
    $isGuest = User::find($userId)->is_guest;

    $completedCharacters = (int) Redis::HGET("room:$roomId:race:player:$userId:progress", 'completedCharacters');
    $wrongCharacters = (int) Redis::HGET("room:$roomId:race:player:$userId:progress", 'wrongCharacters');

    //@ Save result to db
    if (!$isGuest) {
      $accuracyPercentage = $completedCharacters === 0 && $wrongCharacters === 0 ? 0 :
        number_format(
          ($completedCharacters / ($completedCharacters +  $wrongCharacters)) * 100,
          2
        );

      $this->saveNotCompleteResult($roomId, $user, $accuracyPercentage);
    }

    //@ If no more race player -> reset all & delete race player set
    if (!Redis::EXISTS("room:$roomId:race:inRacePlayer")) {
      $this->resetAll($roomId);
      Redis::DEL("room:$roomId:race:player");
      broadcast(new RaceFinished($roomId));
    }

    broadcast(new AbortRace($roomId, $userId));
  }

  public function resetAll($roomId)
  {
    Redis::DEL("room:$roomId:race");
    Redis::DEL("room:$roomId:race:place");

    $this->removeRacePlayerSetAndProgress($roomId);
  }

  public function saveResult(string $roomId, User $player, float $wpm, float $accuracyPercentage, int $finishTime)
  {
    $playerId = $player->id;

    Redis::PIPELINE(function ($pipe) use ($roomId, $playerId) {
      $pipe->SADD("room:$roomId:race:place", $playerId);
      $pipe->EXPIRE("room:$roomId:race:place", 5 * 60);
      $pipe->DEL("user:$playerId:profile"); //@ remove user profile cache
    });

    $place = $this->getRacePlace($roomId);

    //@ Execute this after the race place is set in Redis
    Redis::HMSET("room:$roomId:race:player:$playerId:progress", ["percentage" => 100, "wordsPerMinute" => $wpm, 'finished' => true, 'place' => $place, 'status' => 'completed']);

    if ($place === '1st') {
      Redis::HSET("room:$roomId:race", 'finishTime', $finishTime);
    }

    $averageWpm = (float) Redis::HGET("room:$roomId:player:$playerId", 'averageWpm');
    $racesPlayed = (int) Redis::HGET("room:$roomId:player:$playerId", 'racesPlayed') + 1;
    $totalPlayers = (int) Redis::SCARD("room:$roomId:race:player");
    $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');

    //@ Save result to db
    if (!$player->is_guest) {
      $raceResult = [
        "user_id" => $playerId,
        'quote_id' => $quoteId,
        'wpm' => $wpm,
        'place' => $place,
        'total_players' => $totalPlayers,
        'accuracy_percentage' => $accuracyPercentage
      ];

      RaceResult::create($raceResult);
    }

    $newWpm = number_format(($averageWpm + $wpm) / $racesPlayed, 1);

    $this->updateRedisPlayerStats($roomId, $playerId, $newWpm, $place, $totalPlayers);
  }

  public function saveNotCompleteResult(string $roomId, User $player, float $accuracyPercentage)
  {
    $averageWpm = (float) Redis::HGET("room:$roomId:player:$player->id", 'averageWpm');
    Redis::DEL("user:$player->id:profile");
    $racesPlayed = (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed') + 1;
    $totalPlayers = (int) Redis::SCARD("room:$roomId:race:player");
    $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');

    if (!$player->is_guest) {
      $this->saveNotCompleteResultToDatabase($player->id, $quoteId, $totalPlayers, $accuracyPercentage);
    }

    Redis::DEL("user:$player->id:profile"); //@ remove user profile cache

    $newWpm = number_format($averageWpm / $racesPlayed, 1);

    $this->updateRedisPlayerStats($roomId, $player->id, $newWpm, 'NC', $totalPlayers);
  }

  public function saveNotCompleteResultToDatabase(int $playerId, int $quoteId, int $totalPlayers, float $accuracyPercentage)
  {
    $raceResult = [
      "user_id" => $playerId,
      'quote_id' => $quoteId,
      'wpm' => 0,
      'place' => 'NC',
      'total_players' => $totalPlayers,
      'accuracy_percentage' => $accuracyPercentage
    ];

    RaceResult::create($raceResult);
  }

  public function toggleReadyState(string $roomId, int $playerId, bool $isReady)
  {
    if (!$isReady) {
      Redis::SADD("room:$roomId:race:player", $playerId);
    } else {
      Redis::SREM("room:$roomId:race:player", $playerId);
    }
  }

  public function updateRedisPlayerStats($roomId, $playerId, $wpm, $place, int $totalPlayers)
  {
    $wonPlayers = Redis::SCARD("room:$roomId:race:place");
    $score = $place === 'NC' ? 0 : $totalPlayers - $wonPlayers + 1;

    Redis::pipeline(function ($pipe) use ($roomId, $playerId, $wpm, $place, $score) {
      $pipe->HINCRBY("room:$roomId:player:$playerId", 'racesPlayed', 1);
      $pipe->HSET("room:$roomId:player:$playerId", 'averageWpm', $wpm);
      $pipe->ZINCRBY("room:$roomId:player", $score, $playerId);
      if ($place === '1st') {
        $pipe->HINCRBY("room:$roomId:player:$playerId", 'racesWon', 1);
      }

      //@ Remove playerId in race set
      $pipe->SREM("room:$roomId:race:inRacePlayer", $playerId);
    });
  }

  public function checkHasRaceInProgress(string $roomId)
  {
    $hasRaceInProgress = Redis::EXISTS("room:$roomId:race") === 1;

    if ($hasRaceInProgress) {
      $playerIds = $this->getInRacePlayerIds($roomId);
      return array_map(fn($id) => (int) $id, $playerIds);
    } else {
      return [];
    }
  }

  // HELPER FUNCTIONS
  protected function removeRacePlayerSetAndProgress($roomId)
  {
    $cursor = 0;
    $pattern = "room:$roomId:race:player:*";

    do {
      [$cursor, $keys] = Redis::SCAN($cursor, 'MATCH', $pattern);
      if (!empty($keys)) {
        foreach ($keys as $key) {
          Redis::DEL($key);
        }
      }
    } while ($cursor != 0);
  }
}
