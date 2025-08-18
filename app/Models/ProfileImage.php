<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */

class ProfileImage extends Model
{
  protected $fillable = [
    'public_id',
    'url'
  ];


  public function user(): HasOne
  {
    return $this->hasOne(User::class);
  }
}
