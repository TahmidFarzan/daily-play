<?php

namespace App\Services;

use App\Models\GamePlayResult;
use App\Models\GamePlayRanker;

class GamePlayRankerService
{
    public function recalculateRanks(int $gamePlayId): void
    {
        $scores = GamePlayResult::where('game_play_id', $gamePlayId)
            ->orderBy('duration_ms')
            ->orderBy('backtracks')
            ->orderBy('id')
            ->get();

        $currentRank = 1;

        foreach ($scores as $index => $score) {
            if ($index > 0) {
                $previous = $scores[$index - 1];

                $strictlyBetter = $score->duration_ms > $previous->duration_ms
                    || ($score->duration_ms === $previous->duration_ms && $score->backtracks > $previous->backtracks);

                if ($strictlyBetter) {
                    $currentRank = $index + 1;
                }
            }

            GamePlayRanker::updateOrCreate(
                ['game_play_id' => $score->game_play_id, 'player_id' => $score->player_id],
                ['rank' => $currentRank],
            );
        }
    }

    public function gamePlayRanker(int $gamePlayId, int $playerId): ?int
    {
        $gamePlayResult = GamePlayResult::where('game_play_id', $gamePlayId)
            ->where('player_id', $playerId)
            ->first();

        if (! $gamePlayResult) {
            return null;
        }

        $betterCount = GamePlayResult::where('game_play_id', $gamePlayId)
            ->where(function ($query) use ($gamePlayResult) {
                $query->where('duration_ms', '<', $gamePlayResult->duration_ms)
                    ->orWhere(function ($query) use ($gamePlayResult) {
                        $query->where('duration_ms', '=', $gamePlayResult->duration_ms)
                            ->where('backtracks', '<', $gamePlayResult->backtracks);
                    });
            })
            ->count();

        return $betterCount + 1;
    }

    public function searchTopper(int $gamePlayId, int $limit)
    {
        $scores = GamePlayResult::where('game_play_id', $gamePlayId)
            ->with('player:id,slug,name')
            ->orderBy('duration_ms')
            ->orderBy('backtracks')
            ->orderBy('id')
            ->get();

        $currentRank = 1;
        $result = [];

        foreach ($scores as $index => $score) {
            if ($index > 0) {
                $previous = $scores[$index - 1];

                $strictlyBetter = $score->duration_ms > $previous->duration_ms
                    || ($score->duration_ms === $previous->duration_ms && $score->backtracks > $previous->backtracks);

                if ($strictlyBetter) {
                    $currentRank = $index + 1;
                }
            }

            $result[] = [
                'rank' => $currentRank,
                'player_id' => $score->player_id,
                'player' => $score->player ? [
                    'slug' => $score->player->slug,
                    'name' => $score->player->name,
                ] : null,
                'duration_ms' => (int) $score->duration_ms,
                'backtracks' => (int) $score->backtracks,
            ];
        }

        return array_slice($result, 0, $limit);
    }
}
