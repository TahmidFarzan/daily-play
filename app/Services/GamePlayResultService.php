<?php

namespace App\Services;

use App\Models\GamePlay;
use App\Models\GamePlayResult;
use App\Http\Requests\GamePlayResultRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\GamePlayRankerService;

class GamePlayResultService
{
    protected GamePlayRankerService $gamePlayRankerService;

    public function __construct(GamePlayRankerService $gamePlayRankerService)
    {
        $this->gamePlayRankerService = $gamePlayRankerService;
    }

    public function save(GamePlayResultRequest $request, GamePlay $gamePlay)
    {
        try {
            $playerId = (int) $request->input('player_id');

            $gamePlayResult = DB::transaction(function () use ($request, $gamePlay, $playerId) {

                $gamePlayResult = GamePlayResult::where('game_play_id', $gamePlay->id) ->where('player_id', $playerId)->first();

                if (! $gamePlayResult) {
                    $gamePlayResult = new GamePlayResult();

                    $gamePlayResult->game_play_id = $gamePlay->id;
                    $gamePlayResult->player_id = $playerId;
                    $gamePlayResult->duration_ms = (int) $request->input('duration_ms');
                    $gamePlayResult->backtracks = (int) $request->input('backtracks');
                    $gamePlayResult->device = [
                        'user_agent' => $request->userAgent(),
                        'ip' => $request->ip(),
                        'accept_language' => $request->header('Accept-Language'),
                        'platform' => $request->header('Sec-CH-UA-Platform'),
                        'browser' => $request->header('Sec-CH-UA'),
                        'mobile' => $request->header('Sec-CH-UA-Mobile'),
                    ];

                    $gamePlayResult->save();
                }

                $this->gamePlayRankerService->recalculateRanks($gamePlay->id);

                return $gamePlayResult;
            });

            $topRankers = $this->gamePlayRankerService->searchTopper($gamePlay->id, 10);

            return [
                'status'  => 'success',
                'message' => 'Player score successfully saved.',
                'data'    => [
                    'score' => $gamePlayResult,
                    'rank'  => $this->gamePlayRankerService->gamePlayRanker($gamePlay->id, $gamePlayResult->player_id),
                    'current_player' => $gamePlayResult->player ?? null,
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
