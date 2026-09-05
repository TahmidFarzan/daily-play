<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replace the date-based GamePlay model
     * (game_date + starts_at + ends_at) with an exact
     * 24-hour datetime range (start_at + end_at).
     */
    public function up(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            if (Schema::hasIndex('game_plays', 'game_plays_game_id_game_date_unique')) {
                $table->dropUnique('game_plays_game_id_game_date_unique');
            }
        });

        Schema::table('game_plays', function (Blueprint $table) {
            if (Schema::hasColumn('game_plays', 'game_date')) {
                $table->dropColumn('game_date');
            }

            if (Schema::hasColumn('game_plays', 'starts_at') && ! Schema::hasColumn('game_plays', 'start_at')) {
                $table->renameColumn('starts_at', 'start_at');
            }

            if (Schema::hasColumn('game_plays', 'ends_at') && ! Schema::hasColumn('game_plays', 'end_at')) {
                $table->renameColumn('ends_at', 'end_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            if (Schema::hasColumn('game_plays', 'end_at') && ! Schema::hasColumn('game_plays', 'ends_at')) {
                $table->renameColumn('end_at', 'ends_at');
            }

            if (Schema::hasColumn('game_plays', 'start_at') && ! Schema::hasColumn('game_plays', 'starts_at')) {
                $table->renameColumn('start_at', 'starts_at');
            }

            if (! Schema::hasColumn('game_plays', 'game_date')) {
                $table->date('game_date')->nullable();
            }

            $table->unique(['game_id', 'game_date']);
        });
    }
};
