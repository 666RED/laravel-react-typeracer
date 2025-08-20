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

class UpdatePlayerStats implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * Create a new event instance.
   */
  public function __construct(public string $roomId, public int $userId, public int $score, public float $averageWpm, public int $racesPlayed, public int $racesWon) {}

  public function broadcastWith()
  {
    return [
      'id' => $this->userId,
      'score' => $this->score,
      'averageWpm' => $this->averageWpm,
      'racesPlayed' => $this->racesPlayed,
      'racesWon' => $this->racesWon,
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
      new PrivateChannel("room.$this->roomId"),
    ];
  }
}