<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GamePlay;
use App\Services\Cache\GamePlayCacheService;

class GamePlayService
{
    protected int $cachedTTL = 300;

    protected GamePlayCacheService $gamePlayCacheService;

    public function __construct(GamePlayCacheService $gamePlayCacheService)
    {
        $this->gamePlayCacheService = $gamePlayCacheService;
    }

    public function findByGameSlug(string $pageKey, Game $game): GamePlay
    {
        return $this->gamePlayCacheService->getRecordByGameAndDate($pageKey, $game, now(), $this->cachedTTL);
    }
}
