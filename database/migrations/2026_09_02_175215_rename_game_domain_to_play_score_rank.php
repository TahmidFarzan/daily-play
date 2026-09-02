<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- game_challenges -> game_plays ----
        if (Schema::hasTable('game_challenges') && ! Schema::hasTable('game_plays')) {
            Schema::rename('game_challenges', 'game_plays');

            $this->renameIndexIfExists('game_plays', 'game_challenges_slug_unique', 'game_plays_slug_unique');
            $this->renameIndexIfExists('game_plays', 'game_challenges_game_id_game_date_unique', 'game_plays_game_id_game_date_unique');
        }

        // ---- game_scores -> player_scores ----
        if (Schema::hasTable('game_scores') && ! Schema::hasTable('player_scores')) {
            Schema::rename('game_scores', 'player_scores');

            $this->renameIndexIfExists('player_scores', 'game_scores_slug_unique', 'player_scores_slug_unique');

            if (Schema::hasColumn('player_scores', 'game_challenge_id') && ! Schema::hasColumn('player_scores', 'game_play_id')) {
                Schema::table('player_scores', function (Blueprint $table) {
                    $table->renameColumn('game_challenge_id', 'game_play_id');
                });
            }
        }

        // ---- game_rankers -> player_ranks (rebuild so FK references game_plays.id) ----
        if (Schema::hasTable('game_rankers') && ! Schema::hasTable('player_ranks')) {
            Schema::rename('game_rankers', 'player_ranks');
        }

        if (Schema::hasTable('player_ranks')) {
            $this->rebuildPlayerRanksTable();
        }
    }

    public function down(): void
    {
    }

    /**
     * Rebuild the player_ranks table so its game_play_id foreign key points to
     * game_plays.id (instead of a stale player_scores reference).
     */
    private function rebuildPlayerRanksTable(): void
    {
        $rankTable = 'player_ranks';
        $scoreColumn = Schema::hasColumn($rankTable, 'game_score_id') ? 'game_score_id' : 'game_play_id';

        $map = DB::table($rankTable)
            ->join('player_scores', "$rankTable.$scoreColumn", '=', 'player_scores.id')
            ->select(
                "$rankTable.id as rank_id",
                "$rankTable.player_id",
                "$rankTable.rank",
                "$rankTable.slug",
                "$rankTable.created_at",
                "$rankTable.updated_at",
                'player_scores.game_play_id as real_game_play_id',
            )
            ->get();

        Schema::drop($rankTable);

        Schema::create('player_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_play_id')->constrained('game_plays')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->integer('rank')->nullable()->default(null);
            $table->string('slug')->unique();
            $table->timestamps();
        });

        foreach ($map as $row) {
            DB::table('player_ranks')->insert([
                'game_play_id' => $row->real_game_play_id,
                'player_id' => $row->player_id,
                'rank' => $row->rank,
                'slug' => $row->slug,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function renameIndexIfExists(string $table, string $from, string $to): void
    {
        if (! Schema::hasIndex($table, $from)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($from, $to) {
            $table->renameIndex($from, $to);
        });
    }
};
