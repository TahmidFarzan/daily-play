<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_play_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_play_id')->constrained('game_plays')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->integer('backtracks')->default(0);
            $table->json('device')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_play_results');
    }
};