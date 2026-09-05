<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\GamePlay;
use App\Models\GamePlayRanker;
use App\Models\GamePlayResult;
use App\Models\Player;
use App\Services\GamePlay\ZipBoardGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GamePlaySeeder extends Seeder
{
    public function run(): void
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GamePlayRanker::query()->delete();
            GamePlayResult::query()->delete();
            GamePlay::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='game_plays'");
            DB::statement('PRAGMA foreign_keys = ON;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GamePlayRanker::truncate();
            GamePlayResult::truncate();
            GamePlay::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif (in_array($driver, ['pgsql', 'sqlsrv'])) {
            GamePlayRanker::truncate();
            GamePlayResult::truncate();
            GamePlay::truncate();
        }

        $games = Game::query()
            ->orderBy('id')
            ->get();

        $difficulties = GameDifficulty::query()->get();
        $players = Player::query()->get();

        if (
            $games->isEmpty() ||
            $difficulties->isEmpty() ||
            $players->isEmpty()
        ) {
            return;
        }

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addYear();

        foreach ($games as $game) {
            for (
                $gameDate = $startDate->copy();
                $gameDate->lte($endDate);
                $gameDate->addDay()
            ) {
                $difficulty = $difficulties->random();

                $startAt = $gameDate->copy();
                $endAt = $startAt->copy()->addHours(24);

                switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate(
                            $game,
                            $startAt,
                            $difficulty
                        );
                        break;

                    default:
                        $board = [];
                        break;
                }

                $gamePlay = GamePlay::factory()
                    ->state([
                        'game_id'            => $game->id,
                        'game_difficulty_id' => $difficulty->id,
                        'board'              => $board,
                        'start_at'           => $startAt,
                        'end_at'             => $endAt,
                    ])
                    ->create();

                $selectedPlayers = $players
                    ->shuffle()
                    ->take(fake()->numberBetween(15, 55));

                foreach ($selectedPlayers as $player) {
                    GamePlayResult::factory()
                        ->state([
                            'game_play_id' => $gamePlay->id,
                            'player_id'    => $player->id,
                        ])
                        ->create();
                }

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
            }
        }
    }
}
