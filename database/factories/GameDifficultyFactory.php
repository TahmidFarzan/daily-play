<?php

namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\GameDifficulty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameDifficulty>
 */
class GameDifficultyFactory extends Factory
{
    public function definition(): array
    {
        $adminUser = User::where('is_super_admin', true)->inRandomOrder()->first();
        $difficulties = SeederHelper::GAME_DIFFCULTIES;

        $difficulty = $this->faker->randomElement($difficulties);

        return [
            'name' => $difficulty['name'],
            'brief' => $difficulty['brief'],
            'created_by_id' => $adminUser?->id ?? 1,
        ];
    }
}
