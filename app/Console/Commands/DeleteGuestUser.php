<?php

namespace App\Console\Commands;

use App\Events\Room\RemoveGuestUserSessionRoomId;
use App\Models\User;
use App\Services\RoomHelperService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DeleteGuestUser extends Command
{
  protected $signature = 'app:delete-guest-user';

  protected $description = 'Delete inactive guest users (6 hours) every six hours';

  public function handle()
  {
    $inactiveGuests = User::where('last_active', "<=", Carbon::now()->subHours(6))
      ->where('is_guest', 1)
      ->get();

    foreach ($inactiveGuests as $guest) {
      $roomId = $guest->room_id;
      if ($roomId && Redis::SISMEMBER("available-rooms", $roomId) && Redis::EXISTS("room:$roomId")) {
        //@ if room still exist (set & hash) -> remove player from the room
        $helper = new RoomHelperService();
        $helper->removePlayer($roomId, $guest->id);
        $helper->leaveRoom($roomId, $guest->id, $guest->name);
      }
      broadcast(new RemoveGuestUserSessionRoomId($guest->id));
      $guest->delete();

      $this->line("Inactive guest ($guest->name) has been removed from database");
    }
  }
}
