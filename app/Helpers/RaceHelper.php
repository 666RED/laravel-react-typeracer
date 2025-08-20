<?php

namespace App\Helpers;

use App\Events\Race\AbortRace;
use App\Events\Race\RaceFinished;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;

class RaceHelper extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'race-helper';
  }
}
