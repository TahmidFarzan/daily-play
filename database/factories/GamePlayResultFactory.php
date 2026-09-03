<?php

namespace Database\Factories;

use App\Models\GamePlay;
use App\Models\GamePlayResult;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePlayResult>
 */
class GamePlayResultFactory extends Factory
{
    protected $model = GamePlayResult::class;

    public function definition(): array
    {
        return [
            'game_play_id' => GamePlay::query()->inRandomOrder()->value('id'),
            'player_id'    => Player::query()->inRandomOrder()->value('id'),
            'duration_ms'  => fake()->numberBetween(1000, 86400000),
            'backtracks'   => fake()->numberBetween(0, 10),
            'device'       => [
                'type'      => fake()->randomElement(['mobile', 'tablet', 'desktop']),
                'os'        => fake()->randomElement(['Windows', 'Android', 'iOS', 'macOS', 'Linux']),
                'browser'   => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }
}
