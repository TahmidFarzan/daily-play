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
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_challenge_id')
                ->constrained('game_challenges')
                ->cascadeOnDelete();
            $table->foreignId('player_id')
                ->constrained('players')
                ->cascadeOnDelete();

            $table->string('duration')->default("0");
            $table->integer('back_track')->default("0");
            $table->json('device')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
