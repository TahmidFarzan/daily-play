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
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false)->after('remember_token');
            $table->boolean('is_super_admin')->default(false)->after('is_default');
            $table->date('birth_date')->nullable()->after('is_super_admin');
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Separated', 'Other'])->nullable()->after('birth_date');
            $table->enum('religion', ['Islam', 'Hindu', 'Christian', 'Other'])->nullable()->after('marital_status');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('religion');

            $table->string('mobile', 20)->nullable()->after('religion');
            $table->text('address')->nullable()->after('religion');

            $table->foreignId('created_by_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'is_default',
                'is_super_admin',
                'birth_date',
                'marital_status',
                'religion',
                'gender',
                'mobile',
                'address',
                'created_by_id',
                'deleted_at'
            ]);

            // Drop foreign key constraint
            $table->dropForeign(['created_by_id']);
        });
    }
};
