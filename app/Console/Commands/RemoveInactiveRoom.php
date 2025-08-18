<?php

namespace App\Console\Commands;

use App\Events\Room\RemoveInactiveRoomEvent;
use App\Models\User;
use App\Services\RoomHelperService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RemoveInactiveRoom extends Command
{
  protected $signature = 'app:remove-inactive-room';

  protected $description = 'Remove inactive room (2 hours) every six hours';

  public function handle()
  {
    $availableRooms = Redis::SMEMBERS("available-rooms");

    foreach ($availableRooms as $availableRoom) {
      $expireAt = Redis::EXPIRETIME("room:$availableRoom"); //@ -2 if the room hash already expired
      if ($expireAt <= Carbon::now()->timestamp) {
        $helper = new RoomHelperService();
        $helper->removeRoom($availableRoom);
        User::where('room_id', '=', $availableRoom)->update(['room_id' => null]);
        broadcast(new RemoveInactiveRoomEvent($availableRoom));
        $this->line("Inactive room ($availableRoom) has been removed");
      }
    }
  }
}
