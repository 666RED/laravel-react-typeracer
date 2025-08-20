<?php

use App\Helpers\RaceHelper;
use App\Services\RaceHelperService;

test('Should resolve RaceHelper facade', function () {
  /** @var Tests\TestCase $this */
  $instance = RaceHelper::getFacadeRoot();
  $this->assertInstanceOf(RaceHelperService::class, $instance);
});