<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GamePlay;
use Illuminate\Http\Request;
use App\Services\Cache\GamePlayCacheService;
use Illuminate\Pagination\LengthAwarePaginator;

class GamePlayService
{
    protected int $cachedTTL = 300;

    protected GamePlayCacheService $gamePlayCacheService;

    public function __construct(GamePlayCacheService $gamePlayCacheService)
    {
        $this->gamePlayCacheService = $gamePlayCacheService;
    }

    public function findByGame(string $pageKey, Game $game): GamePlay
    {
        return $this->gamePlayCacheService->getRecordByGameAndDate($pageKey, $game, now(), $this->cachedTTL);
    }

    public function searchByGame(Request $request, string $pageKey, Game $game): LengthAwarePaginator
    {
        return $this->gamePlayCacheService->searchRecordsByGame($pageKey, $game, $request, 10, $this->cachedTTL);
    }
}
