<?php

namespace Database\Seeders;

use App\Models\RaceResult;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RaceResultsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    RaceResult::truncate(); // remove all data in db
    RaceResult::factory(20)->create();
  }
}