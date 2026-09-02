<?php

namespace App\Services;

use App\Models\GameScore;
use App\Models\GameRanker;

class GameRankerService
{
    public function recalculateRanks(int $gameChallengeId): void
    {
        $scores = GameScore::where('game_challenge_id', $gameChallengeId)
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

            GameRanker::updateOrCreate(
                ['game_score_id' => $score->id],
                ['player_id' => $score->player_id, 'rank' => $currentRank],
            );
        }
    }

    public function playerRank(int $gameChallengeId, int $playerId): ?int
    {
        $playerScore = GameScore::where('game_challenge_id', $gameChallengeId)
            ->where('player_id', $playerId)
            ->first();

        if (! $playerScore) {
            return null;
        }

        $betterCount = GameScore::where('game_challenge_id', $gameChallengeId)
            ->where(function ($query) use ($playerScore) {
                $query->where('duration_ms', '<', $playerScore->duration_ms)
                    ->orWhere(function ($query) use ($playerScore) {
                        $query->where('duration_ms', '=', $playerScore->duration_ms)
                            ->where('backtracks', '<', $playerScore->backtracks);
                    });
            })
            ->count();

        return $betterCount + 1;
    }

    public function searchTopper(int $gameChallengeId, int $limit)
    {
        $scores = GameScore::where('game_challenge_id', $gameChallengeId)
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
