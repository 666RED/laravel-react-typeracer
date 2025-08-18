<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class MessageHelper extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'message-helper';
  }
}