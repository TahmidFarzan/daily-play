<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_scores')) {
            Schema::table('game_scores', function (Blueprint $table) {
                if (! Schema::hasColumn('game_scores', 'duration_ms')) {
                    $table->unsignedBigInteger('duration_ms')->default(0)->after('player_id');
                }
            });

            DB::table('game_scores')
                ->whereNull('duration_ms')
                ->update(['duration_ms' => 0]);

            $rows = DB::table('game_scores')->get();
            foreach ($rows as $row) {
                $existingMs = (int) $row->duration_ms;

                if ($existingMs === 0 && (string) $row->duration !== '' && (string) $row->duration !== null) {
                    $seconds = max(0, (int) round((float) $row->duration));
                    DB::table('game_scores')
                        ->where('id', $row->id)
                        ->update(['duration_ms' => $seconds * 1000]);
                }
            }

            if (Schema::hasColumn('game_scores', 'duration')) {
                Schema::table('game_scores', function (Blueprint $table) {
                    $table->dropColumn('duration');
                });
            }

            if (Schema::hasColumn('game_scores', 'back_track')) {
                Schema::table('game_scores', function (Blueprint $table) {
                    $table->renameColumn('back_track', 'backtracks');
                });
            }
        }

        if (Schema::hasTable('game_rankers')) {
            if (Schema::hasColumn('game_rankers', 'score_id') && ! Schema::hasColumn('game_rankers', 'game_score_id')) {
                Schema::table('game_rankers', function (Blueprint $table) {
                    $table->renameColumn('score_id', 'game_score_id');
                });
            }

            if (Schema::hasColumn('game_rankers', 'renker') && ! Schema::hasColumn('game_rankers', 'rank')) {
                Schema::table('game_rankers', function (Blueprint $table) {
                    $table->renameColumn('renker', 'rank');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('game_rankers')) {
            if (Schema::hasColumn('game_rankers', 'rank') && ! Schema::hasColumn('game_rankers', 'renker')) {
                Schema::table('game_rankers', function (Blueprint $table) {
                    $table->renameColumn('rank', 'renker');
                });
            }

            if (Schema::hasColumn('game_rankers', 'game_score_id') && ! Schema::hasColumn('game_rankers', 'score_id')) {
                Schema::table('game_rankers', function (Blueprint $table) {
                    $table->renameColumn('game_score_id', 'score_id');
                });
            }
        }

        if (Schema::hasTable('game_scores')) {
            if (Schema::hasColumn('game_scores', 'backtracks') && ! Schema::hasColumn('game_scores', 'back_track')) {
                Schema::table('game_scores', function (Blueprint $table) {
                    $table->renameColumn('backtracks', 'back_track');
                });
            }

            if (! Schema::hasColumn('game_scores', 'duration')) {
                Schema::table('game_scores', function (Blueprint $table) {
                    $table->string('duration')->default('0');
                });
            }

            foreach (DB::table('game_scores')->get() as $row) {
                DB::table('game_scores')
                    ->where('id', $row->id)
                    ->update(['duration' => (string) floor(((int) $row->duration_ms) / 1000)]);
            }

            if (Schema::hasColumn('game_scores', 'duration_ms')) {
                Schema::table('game_scores', function (Blueprint $table) {
                    $table->dropColumn('duration_ms');
                });
            }
        }
    }
};
