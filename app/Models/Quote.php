<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $text
 * @property string $length
 * @property int $total_characters
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 */

class Quote extends Model
{
  use HasFactory;

  protected $fillable = [
    'text'
  ];

  public function results(): HasMany
  {
    return $this->hasMany(RaceResult::class, 'quote_id');
  }
}
