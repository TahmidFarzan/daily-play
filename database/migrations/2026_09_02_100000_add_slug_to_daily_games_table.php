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
        Schema::table('daily_games', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_games', 'slug')) {
                $table->string('slug')->unique()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_games', function (Blueprint $table) {
            if (Schema::hasColumn('daily_games', 'slug')) {
                $table->dropUnique(['daily_games_slug_unique']);
                $table->dropColumn('slug');
            }
        });
    }
};