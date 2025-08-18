<?php

namespace App\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;

class ProfileHelper extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'profile-helper';
  }
}
