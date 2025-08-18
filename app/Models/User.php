<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/** 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon $email_verified_at
 * @property string $password
 * @property string $room_id
 * @property boolean $is_guest
 * @property int $profile_image_id
 * @property Carbon $created_at
 * @property Carbon $last_active
 * 
 */

class User extends Authenticatable
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable;


  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'id',
    'name',
    'email',
    'password',
    'is_guest'
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'created_at' => 'datetime',
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function results(): HasMany
  {
    return $this->hasMany(RaceResult::class, 'user_id');
  }

  public function profileImage(): BelongsTo
  {
    return $this->belongsTo(ProfileImage::class);
  }

  public function saveRoomId(string $roomId)
  {
    $this->room_id = $roomId;
    $this->save();
  }

  public function removeRoomId()
  {
    $this->room_id = null;
    $this->save();
  }
}
