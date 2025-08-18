<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CloudinaryHelperService;
use App\Services\MessageHelperService;
use App\Services\ProfileHelperService;
use App\Services\RaceHelperService;
use App\Services\RoomHelperService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  public $singletons = [
    'room-helper' => RoomHelperService::class,
    'message-helper' => MessageHelperService::class,
    'cloudinary-helper' => CloudinaryHelperService::class,
    'race-helper' => RaceHelperService::class,
    'profile-helper' => ProfileHelperService::class
  ];

  public function register(): void {}

  public function boot(): void
  {
    //
  }
}
