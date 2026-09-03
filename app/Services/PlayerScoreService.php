<?php

namespace App\Services;

use App\Models\GamePlay;
use App\Models\GamePlayResult;
use App\Http\Requests\GamePlayResultRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\PlayerRankService;

class PlayerScoreService
{
    protected PlayerRankService $playerRankService;

    public function __construct(PlayerRankService $playerRankService)
    {
        $this->playerRankService = $playerRankService;
    }

    public function save(GamePlayResultRequest $request, GamePlay $gamePlay)
    {
        try {
            $playerId = (int) $request->input('player_id');

            $playerScore = DB::transaction(function () use ($request, $gamePlay, $playerId) {

                $playerScore = GamePlayResult::where('game_play_id', $gamePlay->id) ->where('player_id', $playerId)->first();

                if (! $playerScore) {
                    $playerScore = new GamePlayResult();

                    $playerScore->game_play_id = $gamePlay->id;
                    $playerScore->player_id = $playerId;
                    $playerScore->duration_ms = (int) $request->input('duration_ms');
                    $playerScore->backtracks = (int) $request->input('backtracks');
                    $playerScore->device = [
                        'user_agent' => $request->userAgent(),
                        'ip' => $request->ip(),
                        'accept_language' => $request->header('Accept-Language'),
                        'platform' => $request->header('Sec-CH-UA-Platform'),
                        'browser' => $request->header('Sec-CH-UA'),
                        'mobile' => $request->header('Sec-CH-UA-Mobile'),
                    ];

                    $playerScore->save();
                }

                $this->playerRankService->recalculateRanks($gamePlay->id);

                return $playerScore;
            });

            $topRankers = $this->playerRankService->searchTopper($gamePlay->id, 5);

            return [
                'status'  => 'success',
                'message' => 'Player score successfully saved.',
                'data'    => [
                    'score' => $playerScore,
                    'rank'  => $this->playerRankService->playerRank($gamePlay->id, $playerScore->player_id),
                    'current_player' => $playerScore->player ?? null,
                    'top_rankers' => $topRankers,
                ],
            ];
        } catch (Exception $exception) {

            Log::error('Player score save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to save player score. Please try again.',
            ];
        }
    }
}
