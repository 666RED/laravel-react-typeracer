<?php

namespace Database\Seeders;

use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuoteSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $quotes = app()->environment(['testing'])
      ? Storage::json('test-quotes.json')
      : Storage::json('quotes.json');

    $chunks = array_chunk($quotes, 100);

    foreach ($chunks as $chunk) {
      DB::table('quotes')->insert(array_map(fn($quote) => [
        'text' => $quote['text'],
        'total_characters' => mb_strlen(preg_replace('/\s+/u', '', $quote['text'])),
        'length' => $quote['length'],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
      ], $chunk));
    }
  }
}
