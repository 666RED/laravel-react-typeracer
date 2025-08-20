<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class CloudinaryHelper extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'cloudinary-helper';
  }
}
