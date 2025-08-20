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

class RemoveGuestUserSessionRoomId implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;


  /**
   * Create a new event instance.
   */
  public function __construct(public int $userId) {}

  public function broadcastWith()
  {
    return [
      'userId' => $this->userId
    ];
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [
      new PrivateChannel("user.$this->userId")
    ];
  }
}