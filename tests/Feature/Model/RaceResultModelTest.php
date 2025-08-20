<?php

use App\Models\ProfileImage;
use App\Models\Quote;
use App\Models\RaceResult;
use App\Models\User;
use Database\Seeders\QuoteSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

test('RaceResult schema should contain all columns', function () {
  /** @var Tests\TestCase $this */
  $this->assertTrue(
    Schema::hasColumns('race_results', [
      'id',
      'user_id',
      'quote_id',
      'wpm',
      'place',
      'total_players',
      'accuracy_percentage',
      'created_at',
      'updated_at',
    ])
  );
});

test('A race result is belongs to a user', function () {
  /** @var Tests\TestCase $this */
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  $user = User::factory()->create();
  $result = RaceResult::factory()->create([
    'user_id' => $user->id,
  ]);

  $this->assertInstanceOf(User::class, $result->user);
});

test('A race result is belongs to a quote', function () {
  /** @var Tests\TestCase $this */
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  $user = User::factory()->create();
  $quote = Quote::inRandomOrder()->first();
  $result = RaceResult::factory()->create([
    'quote_id' => $quote->id,
    'user_id' => $user->id,
  ]);

  $this->assertInstanceOf(Quote::class, $result->quote);
});
