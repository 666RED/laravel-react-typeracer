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
    Schema::create('profile_images', function (Blueprint $table) {
      $table->id();
      $table->string('public_id');
      $table->string('url');
      $table->timestamps();
    });

    Schema::table('users', function (Blueprint $table) {
      $table->foreignId('profile_image_id')->nullable()->constrained('profile_images')->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('profile_image_id');
    });
    Schema::dropIfExists('profile_images');
  }
};