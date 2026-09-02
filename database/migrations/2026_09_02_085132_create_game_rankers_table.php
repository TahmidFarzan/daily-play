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
        Schema::create('game_rankers', function (Blueprint $table) {
            $table->id();
             $table->foreignId('score_id')
                ->constrained('game_scores')
                ->cascadeOnDelete();
            $table->foreignId('player_id')
                ->constrained('players')
                ->cascadeOnDelete();
            $table->integer('renker')->nullable()->default(null);
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rankers');
    }
};
