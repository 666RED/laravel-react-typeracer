<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    $quotes = [
      ["text" => "Small steps every day lead to big changes over time.", "length" => "short"],
      ["text" => "Be brave enough to start and strong enough to finish.", "length" => "short"],
      ["text" => "Discipline turns talent into consistent performance and progress.", "length" => "short"],
      ["text" => "Growth begins the moment you leave your comfort zone.", "length" => "short"],
      ["text" => "A goal without a plan is just a wish.", "length" => "short"],
      ["text" => "Success doesn't come from luck or chance - it comes from consistent action, clear goals, and unshakable belief.", "length" => "medium"],
      ["text" => "You can't control everything in life, but you can always control how you respond to it.", "length" => "medium"],
      ["text" => "Your future is shaped by what you do today, not tomorrow. Show up, even when it's hard.", "length" => "medium"],
      ["text" => "The difference between who you are and who you want to be is what you do.", "length" => "medium"],
      ["text" => "Most people quit before they reach the point where real change actually begins to happen.", "length" => "medium"],
      ["text" => "There will be days when motivation runs dry and progress feels invisible. But those are the days that matter most - when you persist despite the resistance. That's how growth happens.", "length" => "long"],
      ["text" => "Everyone wants success, but few are willing to endure the boredom of consistent discipline. Success isn't built on excitement - it's built on quiet, repetitive effort that no one sees.", "length" => "long"],
      ["text" => "Don't let temporary setbacks trick you into thinking you're not moving forward. Progress isn't always loud. Sometimes it's just the quiet decision to try again, even when it's hard.", "length" => "long"],
      ["text" => "If you wait for the perfect time, you'll wait forever. Life rewards the doers, the builders, the ones who start before they're ready and learn along the way.", "length" => "long"],
      ["text" => "You won't always feel confident. You won't always feel strong. But if you keep showing up - especially on the hard days - you'll become someone unstoppable without even realizing it.", "length" => "long"],
    ];

    $now = Carbon::now();

    DB::table('quotes')->insert(array_map(fn($q) => [
      'text' => $q['text'],
      'total_characters' => mb_strlen(preg_replace('/\s+/u', '', $q['text'])),
      'length' => $q['length'],
      'created_at' => $now,
      'updated_at' => $now,
    ], $quotes));
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    DB::table('quotes')->whereIn('text', [
      "Small steps every day lead to big changes over time.",
      "Be brave enough to start and strong enough to finish.",
      "Discipline turns talent into consistent performance and progress.",
      "Growth begins the moment you leave your comfort zone.",
      "A goal without a plan is just a wish.",
      "Success doesn't come from luck or chance - it comes from consistent action, clear goals, and unshakable belief.",
      "You can't control everything in life, but you can always control how you respond to it.",
      "Your future is shaped by what you do today, not tomorrow. Show up, even when it's hard.",
      "The difference between who you are and who you want to be is what you do.",
      "Most people quit before they reach the point where real change actually begins to happen.",
      "There will be days when motivation runs dry and progress feels invisible. But those are the days that matter most - when you persist despite the resistance. That's how growth happens.",
      "Everyone wants success, but few are willing to endure the boredom of consistent discipline. Success isn't built on excitement - it's built on quiet, repetitive effort that no one sees.",
      "Don't let temporary setbacks trick you into thinking you're not moving forward. Progress isn't always loud. Sometimes it's just the quiet decision to try again, even when it's hard.",
      "If you wait for the perfect time, you'll wait forever. Life rewards the doers, the builders, the ones who start before they're ready and learn along the way.",
      "You won't always feel confident. You won't always feel strong. But if you keep showing up - especially on the hard days - you'll become someone unstoppable without even realizing it.",
    ])->delete();
  }
};