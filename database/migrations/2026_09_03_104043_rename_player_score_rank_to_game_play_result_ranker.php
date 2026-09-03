<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the player_scores and player_ranks tables to their new
     * domain names (game_play_results / game_play_rankers) while
     * preserving all existing data, columns, foreign keys and indexes.
     */
    public function up(): void
    {
        // ---- player_scores -> game_play_results ----
        if (Schema::hasTable('player_scores') && ! Schema::hasTable('game_play_results')) {
            Schema::rename('player_scores', 'game_play_results');

            $this->renameIndexIfExists('game_play_results', 'player_scores_slug_unique', 'game_play_results_slug_unique');
        }

        // ---- player_ranks -> game_play_rankers ----
        if (Schema::hasTable('player_ranks') && ! Schema::hasTable('game_play_rankers')) {
            Schema::rename('player_ranks', 'game_play_rankers');

            $this->renameIndexIfExists('game_play_rankers', 'player_ranks_slug_unique', 'game_play_rankers_slug_unique');
        }
    }

    public function down(): void
    {
        // ---- game_play_rankers -> player_ranks ----
        if (Schema::hasTable('game_play_rankers') && ! Schema::hasTable('player_ranks')) {
            Schema::rename('game_play_rankers', 'player_ranks');

            $this->renameIndexIfExists('player_ranks', 'game_play_rankers_slug_unique', 'player_ranks_slug_unique');
        }

        // ---- game_play_results -> player_scores ----
        if (Schema::hasTable('game_play_results') && ! Schema::hasTable('player_scores')) {
            Schema::rename('game_play_results', 'player_scores');

            $this->renameIndexIfExists('player_scores', 'game_play_results_slug_unique', 'player_scores_slug_unique');
        }
    }

    private function renameIndexIfExists(string $table, string $from, string $to): void
    {
        if (! Schema::hasIndex($table, $from)) {
            return;
        }

        Schema::table($table, function (Illuminate\Database\Schema\Blueprint $table) use ($from, $to) {
            $table->renameIndex($from, $to);
        });
    }
};
