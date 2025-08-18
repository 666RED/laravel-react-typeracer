<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class RoomHelper extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'room-helper';
  }
}
