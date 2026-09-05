<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replace the datetime-based GamePlay model (start_at + end_at) with an
     * explicit calendar representation (date + start_time + end_time).
     */
    public function up(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });

        DB::table('game_plays')->update([
            'date' => DB::raw('DATE(start_at)'),
            'start_time' => DB::raw('TIME(start_at)'),
            'end_time' => DB::raw('TIME(end_at)'),
        ]);

        Schema::table('game_plays', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
        });

        Schema::table('game_plays', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
        });

        DB::table('game_plays')->update([
            'start_at' => DB::raw("date || ' ' || start_time"),
            'end_at' => DB::raw("date || ' ' || end_time"),
        ]);

        Schema::table('game_plays', function (Blueprint $table) {
            $table->dropColumn(['date', 'start_time', 'end_time']);
        });

        Schema::table('game_plays', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable(false)->change();
            $table->dateTime('end_at')->nullable(false)->change();
        });
    }
};
