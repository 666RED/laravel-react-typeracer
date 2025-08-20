<?php

namespace App\Http\Controllers\Room;

use App\Events\Race\RaceCompleted;
use App\Events\Race\RaceFinished;
use App\Events\Race\RaceNotComplete;
use App\Events\Race\RaceReady;
use App\Events\Race\SetFinishTime;
use App\Events\Race\ToggleReadyState;
use App\Events\Race\UpdateProgress;
use App\Events\Room\UpdatePlayerStats;
use App\Http\Controllers\Controller;
use App\Http\Requests\Race\SaveResultRequest;
use App\Http\Requests\Race\UpdateProgressRequest;
use App\Models\Quote;
use App\Models\RaceResult;
use App\Services\RaceHelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class RaceController extends Controller
{
  public function raceReady(RaceHelperService $raceHelper)
  {
    $ownerId = Auth::id();
    $roomId = session('roomId');

    $raceHelper->resetAll($roomId);
    $raceHelper->setRaceReady($roomId, $ownerId);
    $raceHelper->setRacePlayersInitialState($roomId);

    $playerIds = $raceHelper->getPlayerIds($roomId);

    broadcast(new RaceReady($roomId, $playerIds));

    return response()->noContent();
  }

  public function updateProgress(UpdateProgressRequest $request)
  {
    $roomId = session('roomId');
    $userId = Auth::id();
    $validated = $request->validated();

    $percentage = $validated['percentage'];
    $wordsPerMinute = $validated['wordsPerMinute'];
    $completedCharacters = $validated['completedCharacters'];
    $wrongCharacters = $validated['wrongCharacters'];

    //@ Save to redis
    $playerProgress = [
      'playerId' => $userId,
      'percentage' => $percentage,
      'wordsPerMinute' => $wordsPerMinute,
      'completedCharacters' => $completedCharacters,
      'wrongCharacters' => $wrongCharacters
    ];

    Redis::HMSET("room:$roomId:race:player:$userId:progress", $playerProgress);

    broadcast(new UpdateProgress($roomId, $userId, $percentage, $wordsPerMinute));

    return response()->noContent();
  }

  public function saveResult(SaveResultRequest $request, RaceHelperService $raceHelper)
  {
    $validated = $request->validated();
    $wpm = $validated['wpm'];
    $accuracyPercentage = $validated['accuracyPercentage'];

    $roomId = session('roomId');
    $player = Auth::user();

    $finishTime = now()->addSeconds(20)->timestamp;

    $raceHelper->saveResult($roomId, $player, $wpm, $accuracyPercentage, $finishTime);

    $score = (int) Redis::ZSCORE("room:$roomId:player", $player->id);
    $racesPlayed = (int) Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed');
    $racesWon = (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon');
    $place = $raceHelper->getRacePlace($roomId);

    if ($place === '1st') {
      broadcast(new SetFinishTime($roomId, $finishTime));
    }

    broadcast(new RaceCompleted($roomId, $player->id, $wpm, $place));
    broadcast(new UpdatePlayerStats($roomId, $player->id, $score, $wpm, $racesPlayed, $racesWon));

    //@ If no player left in set -> remove race
    if (!Redis::EXISTS("room:$roomId:race:inRacePlayer")) {
      $raceHelper->resetAll($roomId);
      Redis::DEL("room:$roomId:race:player");
      broadcast(new RaceFinished($roomId));
    }

    return response()->noContent();
  }

  public function saveNotCompleteResult(Request $request, RaceHelperService $raceHelper)
  {
    $validated = $request->validate([
      'accuracyPercentage' => 'required|numeric|between:0,100'
    ]);
    $accuracyPercentage = $validated['accuracyPercentage'];

    $roomId = session('roomId');

    $player = Auth::user();

    $raceHelper->saveNotCompleteResult($roomId, $player, $accuracyPercentage);

    $score = (int) Redis::ZSCORE("room:$roomId:player", $player->id);
    $averageWpm = Redis::HGET("room:$roomId:player:$player->id", 'averageWpm');
    $racesPlayed = Redis::HGET("room:$roomId:player:$player->id", 'racesPlayed') + 1;
    $racesWon = (int) Redis::HGET("room:$roomId:player:$player->id", 'racesWon');

    broadcast(new RaceNotComplete($roomId, $player->id));
    broadcast(new UpdatePlayerStats($roomId, $player->id, $score, $averageWpm, $racesPlayed, $racesWon));

    //@ If no player left in set -> remove race
    if (!Redis::EXISTS("room:$roomId:race:inRacePlayer")) {
      $raceHelper->resetAll($roomId);
      Redis::DEL("room:$roomId:race:player");
      broadcast(new RaceFinished($roomId));
    }

    return response()->noContent();
  }

  public function toggleReadyState(RaceHelperService $helper)
  {
    $roomId = session('roomId');
    $playerId = Auth::id();
    $ownerId = (int) Redis::HGET("room:$roomId", 'owner');

    if ($playerId === $ownerId) {
      return back()->withErrors('Owner is not allowed to toggle ready state');
    }

    $isReady = Redis::SISMEMBER("room:$roomId:race:player", $playerId) === 1;

    $helper->toggleReadyState($roomId, $playerId, $isReady);

    broadcast(new ToggleReadyState($roomId, $playerId, !$isReady));

    return response()->noContent();
  }
}
