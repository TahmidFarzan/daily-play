<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GamePlay;
use App\Models\GamePlayRanker;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class GamePlayRankerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            GamePlayRanker::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='game_play_rankers'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            GamePlayRanker::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GamePlayRanker::truncate();
        }

        GamePlay::query()
            ->with('gamePlayResults')
            ->orderBy('id')
            ->each(function (GamePlay $gamePlay) {
                $results = $gamePlay->gamePlayResults
                    ->sort(function ($a, $b) {
                        $durationCompare = $a->duration_ms <=> $b->duration_ms;

                        if ($durationCompare !== 0) {
                            return $durationCompare;
                        }

                        return $a->backtracks <=> $b->backtracks;
                    })
                    ->values();

                foreach ($results as $index => $result) {
                    GamePlayRanker::factory()
                        ->state([
                            'game_play_id' => $gamePlay->id,
                            'player_id'    => $result->player_id,
                            'rank'         => $index + 1,
                        ])
                        ->create();
                }
            });
    }
}
