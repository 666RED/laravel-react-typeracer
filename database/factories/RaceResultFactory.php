<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RaceResult>
 */
class RaceResultFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $place = fake()->randomElement(['1st', '2nd', '3rd', '4th', '5th']);
    $placeInt = (int) substr($place, 0, 1);

    return [
      'user_id' => User::where('is_guest', '=', 0)->inRandomOrder()->first()->id,
      'quote_id' => Quote::inRandomOrder()->first()->id,
      'wpm' => fake()->randomFloat(1, 0, 200),
      'accuracy_percentage' => fake()->randomFloat(2, 0, 100),
      'place' => $place,
      'total_players' => fake()->numberBetween($placeInt, 5),
      'created_at' => fake()->dateTimeBetween('-1 month', 'now')
    ];
  }
}
