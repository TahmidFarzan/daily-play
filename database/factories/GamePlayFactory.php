<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\GamePlay;
use Carbon\Carbon;
use App\Services\GamePlay\ZipBoardGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePlay>
 */
class GamePlayFactory extends Factory
{
    protected $model = GamePlay::class;

    public function definition(): array
    {
        $gameDate = fake()->dateTimeBetween('today', '+1 year');
        $gameDate = Carbon::instance($gameDate)->startOfDay();

        $board = [];
        $game = Game::query()->inRandomOrder()->first();
        $gameDifficulty = GameDifficulty::query()->inRandomOrder()->first();

        switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate(
                            $game,
                            $gameDate,
                            $gameDifficulty
                        );
                        break;

                    default:
                        $board = [];
                        break;
                }

        return [
            'game_id'            => $game?->id,
            'game_difficulty_id' => $gameDifficulty?->id,
            'game_date'          => $gameDate->toDateString(),
            'board'              => $board,
            'starts_at'          => $gameDate->copy()->startOfDay(),
            'ends_at'            => $gameDate->copy()->endOfDay(),
        ];
    }
}
