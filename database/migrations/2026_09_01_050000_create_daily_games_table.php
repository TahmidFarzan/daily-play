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
        Schema::create('daily_games', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->foreignId('game_difficulty_id')
                ->constrained('game_difficulties')->nullable()->cascadeOnDelete();

            $table->date('game_date');
            $table->json('board');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->unique(['game_id', 'game_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_games');
    }
};
