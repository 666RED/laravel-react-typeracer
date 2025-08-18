<?php

use App\Console\Commands\DeleteGuestUser;
use App\Console\Commands\RemoveInactiveRoom;
use Illuminate\Support\Facades\Schedule;

Schedule::everySixHours()->group(function () {
  Schedule::command(DeleteGuestUser::class);
  Schedule::command(RemoveInactiveRoom::class);
});