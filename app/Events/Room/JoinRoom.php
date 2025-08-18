<?php

namespace App\Events\Room;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JoinRoom implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * Create a new event instance.
   */
  public function __construct(public string $roomId, public $player, public int $playerId, public int $playerCount)
  {
    //
  }

  public function broadcastWith()
  {
    return [
      'roomId' => $this->roomId,
      'player' => $this->player,
      'playerId' => $this->playerId,
      'playerCount' => $this->playerCount
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
      new Channel('public-rooms'),
      new PrivateChannel("room.$this->roomId"),
    ];
  }
}