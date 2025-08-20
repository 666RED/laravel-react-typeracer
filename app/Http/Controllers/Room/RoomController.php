<?php

namespace App\Http\Controllers\Room;

use App\Events\Room\DeleteRoom;
use App\Events\Room\NewRoomCreated;
use App\Events\Room\RemoveRoomInLobby;
use App\Events\Room\UpdateRoomInLobby;
use App\Events\Room\UpdateRoomSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Room\CreateRoomRequest;
use App\Http\Requests\Room\UpdateRoomSettingRequest;
use App\Models\Quote;
use App\Models\User;
use App\Services\MessageHelperService;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class RoomController extends Controller
{
  //@ GET ROUTES
  public function showRoom($roomId, RaceHelperService $raceHelper)
  {
    $playersFn = fn() => $raceHelper->getRoomPlayers($roomId);
    $racePlayerIds = $raceHelper->checkHasRaceInProgress($roomId);

    return Inertia::render('room/index', ['players' => $playersFn, 'racePlayerIds' => $racePlayerIds]);
  }

  public function showRace($roomId, RaceHelperService $raceHelper)
  {
    $userId = Auth::id();

    $isPlayerInRace = Redis::SISMEMBER("room:$roomId:race:inRacePlayer", $userId);
    $finishTime = (int) Redis::HGET("room:$roomId:race", 'finishTime');

    //@ When user finished the race and refresh tha page -> bring back to waiting room
    if (!$isPlayerInRace) {
      if (Redis::HGET("room:$roomId:race:player:$userId:progress", 'status') === 'abort') {
        return to_route('room.show', ['roomId' => $roomId])->with(['message' => 'You have aborted the race', 'type' => 'warning']);
      } else {
        return to_route('room.show', ['roomId' => $roomId])->with(['message' => 'You have completed the race']);
      }
    }

    //@ Race aborted (exceed finish time)
    if ($finishTime > 0 && $finishTime < Carbon::now()->timestamp) {
      $raceHelper->abortRace($roomId, Auth::user());
      return to_route('room.show', ['roomId' => $roomId])->withErrors(['message' => 'Race aborted']);
    }

    $startTime = (int) Redis::HGET("room:$roomId:race", 'startTime');
    $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');
    $quote = Quote::select('id', 'text')->find($quoteId);
    $completedCharacters = (int) Redis::HGET("room:$roomId:race:player:$userId:progress", 'completedCharacters');
    $wrongCharacters = (int) Redis::HGET("room:$roomId:race:player:$userId:progress", 'wrongCharacters');
    $inRacePlayers = $raceHelper->getAllPlayers($roomId);

    return Inertia::render('room/race', ['startTime' => $startTime, 'finishTime' => $finishTime, 'quote' => $quote, 'inRacePlayers' => $inRacePlayers, 'completedCharacters' => $completedCharacters, 'wrongCharacters' => $wrongCharacters]);
  }

  public function spectateRace($roomId, MessageHelperService $messageHelper, RaceHelperService $raceHelper)
  {
    $startTime = (int) Redis::HGET("room:$roomId:race", 'startTime');
    $finishTime = (int) Redis::HGET("room:$roomId:race", 'finishTime');
    $quoteId = (int) Redis::HGET("room:$roomId:race", 'quoteId');

    $inRacePlayers = $raceHelper->getAllPlayers($roomId);
    $players = $raceHelper->getRoomPlayers($roomId);
    $messages = $messageHelper->getMessages($roomId);
    $quote = Quote::select('id', 'text')->find($quoteId);

    return Inertia::render('room/spectate', ['messages' => $messages, 'inRacePlayers' => $inRacePlayers, 'players' => $players, 'startTime' => $startTime, 'finishTime' => $finishTime, 'quote' => $quote]);
  }

  //@ POST ROUTES
  public function createRoom(CreateRoomRequest $request, RoomHelperService $roomHelper, MessageHelperService $messageHelper)
  {
    $validated = $request->validated();
    $name = $validated['name'];
    $roomId = $validated['roomId'];
    $playerCount = $validated['playerCount'];
    $private = $validated['private'];

    $room = [
      'id' => $roomId,
      'name' => $name,
      'owner' => Auth::id(),
      'playerCount' => 0,
      'maxPlayer' => $playerCount,
      'private' => $private === true ? '1' : '0'
    ];

    $roomHelper->createRoom($roomId, $room, Auth::user());

    session()->put('roomId', $roomId);
    User::find(Auth::id())?->saveRoomId($roomId);

    // ? push room creation message
    $senderId = Auth::id();
    $senderName = Auth::user()->name;

    $messageHelper->pushMessage($roomId, ['text' => "$senderName created the room", 'senderId' => $senderId, 'senderName' => $senderName, 'isNotification' => true]);

    return to_route('room.show', ['roomId' => $roomId]);
  }

  public function joinRoom(Request $request, RoomHelperService $helper)
  {
    $validated = $request->validate([
      'roomId' => 'required|uuid'
    ]);

    $roomId = $validated['roomId'];

    $user = Auth::user();

    $success = $helper->joinRoom($roomId, $user->id, $user->name);

    if (!$success) {
      return back()->withErrors(['message' => 'Room is full']);
    }

    session()->put('roomId', $roomId);
    User::find(Auth::id())?->saveRoomId($roomId);

    return to_route('room.show', ['roomId' => $roomId]);
  }

  public function leaveRoom(RoomHelperService $helper)
  {
    $roomId = session('roomId');
    $user = Auth::user();
    $roomDeleted = $helper->removePlayer($roomId, $user->id);
    if (!$roomDeleted) {
      $helper->leaveRoom($roomId, $user->id, $user->name);
    }

    session()->remove('roomId');
    User::find(Auth::id())?->removeRoomId();

    return to_route('home');
  }

  public function removeFromRoom(Request $request)
  {
    $message = $request->post('message');

    session()->remove('roomId');
    User::find(Auth::id())?->removeRoomId();

    return to_route('home')->with(['message' => $message, 'type' => 'warning']);
  }

  public function deleteRoom(RoomHelperService $helper)
  {
    $roomId = session('roomId');
    $owner = (int) Redis::HGET("room:$roomId", 'owner');

    $helper->removeRoom($roomId);

    broadcast(new DeleteRoom($roomId, $owner));

    session()->remove('roomId');

    User::find(Auth::id())?->removeRoomId();

    return to_route('home');
  }

  public function transferOwnershipAndJoinNewRoom(Request $request, RoomHelperService $helper)
  {
    $previousRoomId = session('roomId');
    $validated = $request->validate([
      'roomId' => 'required|uuid'
    ]);
    $newRoomId = $validated['roomId'];
    $user = Auth::user();

    //@ 1. Remove player from previous room hash & sorted set
    $roomDeleted = $helper->removePlayer($previousRoomId, $user->id);

    //@ 2. Transfer ownership to other player if room not deleted
    if (!$roomDeleted) {
      $helper->transferOwnership($previousRoomId);
      $helper->leaveRoom($previousRoomId, $user->id, $user->name);
    }

    //@ remove old room id
    session()->remove('roomId');
    User::find(Auth::id())?->removeRoomId();

    //@ 3. Join new room
    $success = $helper->joinRoom($newRoomId, $user->id, $user->name);

    if (!$success) {
      return back()->withErrors(['message' => 'Room is full']);
    }

    session()->put('roomId', $newRoomId);
    User::find(Auth::id())?->saveRoomId($newRoomId);

    return to_route('room.show', ['roomId' => $newRoomId]);
  }

  public function transferOwnershipAndLeave(RoomHelperService $helper)
  {
    $roomId = session('roomId');
    $user = Auth::user();
    $roomDeleted = $helper->removePlayer($roomId, $user->id);

    if (!$roomDeleted) {
      $helper->transferOwnership($roomId);
      $helper->leaveRoom($roomId, $user->id, $user->name);
    }

    session()->remove('roomId');
    User::find(Auth::id())?->removeRoomId();

    return to_route('home');
  }

  public function leavePreviousRoomAndJoinNewRoom(Request $request, RoomHelperService $helper)
  {
    $validated = $request->validate([
      'roomId' => 'required|uuid'
    ]);
    $roomId = session('roomId');
    $newRoomId = $validated['roomId'];
    $user = Auth::user();

    $roomDeleted = $helper->removePlayer($roomId, $user->id);

    if (!$roomDeleted) {
      $helper->leaveRoom($roomId, $user->id, $user->name);
    }

    //@ Remove previous room id
    session()->remove("roomId");
    User::find(Auth::id())?->removeRoomId();

    $success = $helper->joinRoom($newRoomId, $user->id, $user->name);

    if (!$success) {
      return back()->withErrors(['message' => 'Room is full']);
    }

    session()->put('roomId', $newRoomId);
    User::find(Auth::id())?->saveRoomId($newRoomId);

    return to_route('room.show', ['roomId' => $newRoomId]);
  }

  public function updateRoomSetting(UpdateRoomSettingRequest $request, MessageHelperService $messageHelper)
  {
    $validated = $request->validated();

    $name = $validated['name'];
    $maxPlayer = $validated['maxPlayer'];
    $owner = $validated['owner'];
    $private = $validated['private'] ? '1' : '0';

    $roomId = session('roomId');

    $previousName = Redis::HGET("room:$roomId", 'name');
    $previousMaxPlayer = (int) Redis::HGET("room:$roomId", 'maxPlayer');
    $previousPrivate = Redis::HGET("room:$roomId", 'private');
    $playerCount = (int) Redis::HGET("room:$roomId", 'playerCount');

    if ($playerCount > $maxPlayer) {
      return back()->withErrors('# Players should not less than number of players in the room');
    }

    //@ Update room in Redis
    Redis::HMSET("room:$roomId", ['name' => $name, 'maxPlayer' => $maxPlayer, 'owner' => $owner, 'private' => $private]);

    $updatedRoom = Redis::HGETALL("room:$roomId");

    //@ Transfer ownership
    if ($updatedRoom['owner'] !== (string) Auth::id()) {
      $newOwner = $updatedRoom['owner'];
      $newOwnerName = User::find($newOwner)->name;

      Redis::SREM("room:$roomId:race:player", $newOwner); //@ remove new owner isReady state (remove from race:player set)

      $messageHelper->pushMessage($roomId, ['text' => "$newOwnerName is the room owner now", 'senderId' => $newOwner, 'senderName' => $newOwnerName, 'isNotification' => true]);
    }

    //@ Update room max player OR room name in lobby
    if ($previousMaxPlayer !== $maxPlayer || $previousName !== $name) {
      broadcast(new UpdateRoomInLobby($roomId, $maxPlayer, $name));
    }

    //@ Remove room / create room in lobby if private setting changed
    if ($previousPrivate !== $private) {
      if ($private === '1') {
        broadcast(new RemoveRoomInLobby($roomId));
      } else {
        broadcast(new NewRoomCreated($updatedRoom));
      }
    }

    //@ if transfer ownership, reset isReady state of new owner
    $owner = (int) Redis::HGET("room:$roomId", 'owner');
    broadcast(new UpdateRoomSetting($roomId, $owner));
    return back()->with(['message' => 'Room setting updated', 'type' => 'success']);
  }
}
