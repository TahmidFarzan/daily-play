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
        $startAt = Carbon::instance(fake()->dateTimeBetween('today', '+1 year'));
        $endAt = $startAt->copy()->addHours(24);

        $board = [];
        $game = Game::query()->inRandomOrder()->first();
        $gameDifficulty = GameDifficulty::query()->inRandomOrder()->first();

        switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate(
                            $game,
                            $startAt,
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
            'board'              => $board,
            'start_at'           => $startAt,
            'end_at'             => $endAt,
        ];
    }
}
