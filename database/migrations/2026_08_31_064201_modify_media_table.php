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
        Schema::table('media', function (Blueprint $table) {
            // Drop existing morphs columns
            $table->dropMorphs('model');

            // Add new columns
            $table->string('slug')->unique()->nullable();
            $table->nullableMorphs('model');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn('slug');
            $table->dropMorphs('model');
            $table->dropForeign(['created_by_id']);
            $table->dropColumn('created_by_id');

            // Recreate original morphs
            $table->morphs('model');
        });
    }
};
