<?php

use App\Models\Quote;
use App\Models\RaceResult;
use App\Models\User;
use Database\Seeders\QuoteSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

test('Quote schema should contain all columns', function () {
  /** @var Tests\TestCase $this */
  $this->assertTrue(
    Schema::hasColumns('quotes', [
      'id',
      'text',
      'length',
      'total_characters',
      'created_at',
      'updated_at',
    ])
  );
});

test('A quote has one or many results', function () {
  /** @var Tests\TestCase $this */
  $quoteSeeder = new QuoteSeeder;
  $quoteSeeder->run();

  $user = User::factory()->create();
  $quote = Quote::inRandomOrder(1)->first();
  $result = RaceResult::factory()->create([
    'user_id' => $user->id,
    'quote_id' => $quote->id
  ]);

  $this->assertTrue($quote->results->contains($result));
  $this->assertEquals(1, $quote->results->count());
  $this->assertInstanceOf(Collection::class, $quote->results);
});
