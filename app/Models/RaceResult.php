<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $quote_id
 * @property float $wpm
 * @property string $place
 * @property int $total_players
 * @property float $accuracy_percentage
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */

class RaceResult extends Model
{
  /** @use HasFactory<\Database\Factories\ResultFactory> */
  use HasFactory;

  protected $fillable = [
    'user_id',
    'quote_id',
    'wpm',
    'place',
    'total_players',
    'accuracy_percentage'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function quote(): BelongsTo
  {
    return $this->belongsTo(Quote::class, 'quote_id');
  }
}
