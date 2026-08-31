<?php

namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\GameDifficulty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameDifficultySeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GameDifficulty::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='game_difficulties'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GameDifficulty::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GameDifficulty::truncate();
        }

        $difficulties = SeederHelper::GAME_DIFFCULTY;

        foreach ($difficulties as $difficulty) {
            GameDifficulty::factory()->state([
                'name' => $difficulty['name'],
                'brief' => $difficulty['brief'],
            ])->create();
        }
    }
}
