<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\GamePlay;
use App\Services\GamePlay\ZipBoardGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePlay>
 */
class GamePlayFactory extends Factory
{
    protected $model = GamePlay::class;

    public function definition(): array
    {
        $date = Carbon::instance(fake()->dateTimeBetween('today', '+1 year'));

        $board = [];
        $game = Game::query()->inRandomOrder()->first();
        $gameDifficulty = GameDifficulty::query()->inRandomOrder()->first();

        switch ($game->slug) {
            case 'zip':
                $board = app(ZipBoardGenerator::class)->generate(
                    $game,
                    $date,
                    $gameDifficulty
                );
                break;

            default:
                $board = [];
                break;
        }

        return [
            'game_id' => $game?->id,
            'game_difficulty_id' => $gameDifficulty?->id,
            'board' => $board,
            'date' => $date->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ];
    }
}
