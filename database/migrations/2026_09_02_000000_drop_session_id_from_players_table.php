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
        if (Schema::hasColumn('players', 'session_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropUnique(['session_id']);
                $table->dropColumn('session_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('players', 'session_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->text('session_id')->nullable()->unique();
            });
        }
    }
};