<?php

namespace Database\Factories;

use App\Models\UserPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPermission>
 */
class UserPermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module' => $this->faker->name(),
            'access' => $this->faker->name(),
        ];
    }
}
