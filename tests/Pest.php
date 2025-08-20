<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;


pest()->extend(TestCase::class)
  ->use(RefreshDatabase::class)
  ->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
  return $this->toBe(1);
});
