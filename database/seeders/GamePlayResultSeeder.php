<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GamePlay;
use App\Models\GamePlayResult;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class GamePlayResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            GamePlayResult::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='game_play_results'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            GamePlayResult::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GamePlayResult::truncate();
        }

        $players = Player::query()->get();

        GamePlay::query()
            ->orderBy('id')
            ->each(function (GamePlay $gamePlay) use ($players) {
                $playerCount = fake()->numberBetween(15, 55);

                $selectedPlayers = $players
                    ->shuffle()
                    ->take(min($playerCount, $players->count()));

                foreach ($selectedPlayers as $player) {
                    GamePlayResult::factory()
                        ->state([
                            'game_play_id' => $gamePlay->id,
                            'player_id'    => $player->id,
                        ])
                        ->create();
                }
            });
    }
}
