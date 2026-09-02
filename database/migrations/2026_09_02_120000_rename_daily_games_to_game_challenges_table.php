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
        Schema::rename('daily_games', 'game_challenges');

        Schema::table('game_challenges', function (Blueprint $table) {
            if (Schema::hasIndex('game_challenges', 'daily_games_slug_unique')) {
                $table->renameIndex('daily_games_slug_unique', 'game_challenges_slug_unique');
            }

            if (Schema::hasIndex('game_challenges', 'daily_games_game_id_game_date_unique')) {
                $table->renameIndex('daily_games_game_id_game_date_unique', 'game_challenges_game_id_game_date_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('game_challenges', 'daily_games');

        Schema::table('daily_games', function (Blueprint $table) {
            if (Schema::hasIndex('daily_games', 'game_challenges_slug_unique')) {
                $table->renameIndex('game_challenges_slug_unique', 'daily_games_slug_unique');
            }

            if (Schema::hasIndex('daily_games', 'game_challenges_game_id_game_date_unique')) {
                $table->renameIndex('game_challenges_game_id_game_date_unique', 'daily_games_game_id_game_date_unique');
            }
        });
    }
};