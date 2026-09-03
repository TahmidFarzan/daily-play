<?php

namespace Database\Factories;

use App\Models\GamePlay;
use App\Models\GamePlayRanker;
use App\Models\GamePlayResult;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePlayRanker>
 */
class GamePlayRankerFactory extends Factory
{
    protected $model = GamePlayRanker::class;

    public function definition(): array
    {
        $gamePlay = GamePlay::query()
            ->inRandomOrder()
            ->first();

        $player = Player::query()
            ->inRandomOrder()
            ->first();

        if (!$gamePlay || !$player) {
            return [
                'game_play_id' => $gamePlay?->id,
                'player_id'    => $player?->id,
                'rank'         => fake()->numberBetween(1, 55),
            ];
        }

        $gamePlayResults = GamePlayResult::query()
            ->where('game_play_id', $gamePlay->id)
            ->whereNot('player_id', $player->id)
            ->get();

        $playerGamePlayResult = GamePlayResult::query()
            ->where('game_play_id', $gamePlay->id)
            ->where('player_id', $player->id)
            ->first();

        if (!$playerGamePlayResult) {
            return [
                'game_play_id' => $gamePlay->id,
                'player_id'    => $player->id,
                'rank'         => fake()->numberBetween(1, 55),
            ];
        }

        $rank = 1;

        foreach ($gamePlayResults as $result) {
            if (
                $result->duration_ms < $playerGamePlayResult->duration_ms ||
                (
                    $result->duration_ms === $playerGamePlayResult->duration_ms &&
                    $result->backtracks < $playerGamePlayResult->backtracks
                )
            ) {
                $rank++;
            }
        }

        return [
            'game_play_id' => $gamePlay->id,
            'player_id'    => $player->id,
            'rank'         => $rank,
        ];
    }
}
