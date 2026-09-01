<?php

namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminUser = User::where('is_super_admin', true)->inRandomOrder()->first();
        $games = SeederHelper::GAMES;

        $game = $this->faker->randomElement($games);

        return [
            'name' => $game['name'],
            'brief' => $game['brief'],
            'how_to_play' => $game['how_to_play'],
            'created_by_id' => $adminUser?->id ?? 1,
        ];
    }
}
