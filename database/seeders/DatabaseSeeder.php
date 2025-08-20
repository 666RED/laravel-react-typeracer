<?php

namespace Database\Seeders;

use App\Events\RaceReady;
use App\Models\Quote;
use App\Models\RaceResult;
use App\Models\Result;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {

    // ? Create 1 test user
    User::factory()->create([
      'name' => 'Test',
      'email' => 'test@gmail.com',
      'password' => '12341234'
    ]);
    // Create quotes
    $this->call([
      QuoteSeeder::class, // Create quotes
      RaceResultsSeeder::class // Create 20 results
    ]);
  }
}
