<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('race_results', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users');
      $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
      $table->decimal('wpm', 4, 1);
      $table->enum('place', ['1st', '2nd', '3rd', '4th', '5th', 'NC']);
      $table->integer('total_players');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('race_results');
  }
};