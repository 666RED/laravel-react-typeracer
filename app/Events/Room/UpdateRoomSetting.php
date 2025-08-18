<?php

namespace App\Events\Room;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRoomSetting implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;


  /**
   * Create a new event instance.
   */

  public function __construct(public string $roomId, public int $owner) {}

  public function broadcastWith()
  {
    return ['owner' => $this->owner];
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [
      new PrivateChannel("room.$this->roomId"),
    ];
  }
}