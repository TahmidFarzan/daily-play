<?php

namespace App\Services;

use App\Models\GameChallenge;
use App\Models\GameScore;
use App\Http\Requests\GameScoreRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\GameRankerService;

class GameScoreService
{
    protected GameRankerService $gameRankerService;

    public function __construct(GameRankerService $gameRankerService)
    {
        $this->gameRankerService = $gameRankerService;
    }

    public function save(GameScoreRequest $request, GameChallenge $gameChallenge)
    {
        try {
            $playerId = (int) $request->input('player_id');

            $gameScore = DB::transaction(function () use ($request, $gameChallenge, $playerId) {

                $gameScore = GameScore::where('game_challenge_id', $gameChallenge->id) ->where('player_id', $playerId)->first();

                if (! $gameScore) {
                    $gameScore = new GameScore();

                    $gameScore->game_challenge_id = $gameChallenge->id;
                    $gameScore->player_id = $playerId;
                    $gameScore->duration_ms = (int) $request->input('duration_ms');
                    $gameScore->backtracks = (int) $request->input('backtracks');
                    $gameScore->device = [
                        'user_agent' => $request->userAgent(),
                        'ip' => $request->ip(),
                        'accept_language' => $request->header('Accept-Language'),
                        'platform' => $request->header('Sec-CH-UA-Platform'),
                        'browser' => $request->header('Sec-CH-UA'),
                        'mobile' => $request->header('Sec-CH-UA-Mobile'),
                    ];

                    $gameScore->save();
                }

                $this->gameRankerService->recalculateRanks($gameChallenge->id);

                return $gameScore;
            });

            $topRankers = $this->gameRankerService->searchTopper($gameChallenge->id, 5);

            return [
                'status'  => 'success',
                'message' => 'Game score successfully save.',
                'data'    => [
                    'score' => $gameScore,
                    'rank'  => $this->gameRankerService->playerRank($gameChallenge->id, $gameScore->player_id),
                    'current_player' => $gameScore->player ?? null,
                    'top_rankers' => $topRankers,
                ],
            ];
        } catch (Exception $exception) {

            Log::error('Game score save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to save game score. Please try again.',
            ];
        }
    }
}
