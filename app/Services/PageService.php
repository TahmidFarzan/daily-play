<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Models\DailyGame;
use App\Models\Game;
use App\Services\Cache\DailyGameCacheService;
use App\Services\Cache\GameCacheService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PageService
{
    protected int $cachedTTL = 300;

    protected GameCacheService $gameCacheService;

    protected DailyGameCacheService $dailyGameCacheService;

    public function __construct(GameCacheService $gameCacheService, DailyGameCacheService $dailyGameCacheService)
    {
        $this->gameCacheService = $gameCacheService;
        $this->dailyGameCacheService = $dailyGameCacheService;
    }

    public function gameBySlug(string $pageKey, string $slug): Game
    {
        $fullKey = $pageKey;
        $fullKey .= ':'.CacheHelper::KEY_GAME;

        return $this->gameCacheService->getRecordBySlug($fullKey, $slug, $this->cachedTTL);
    }

    public function dailyGameByGameSlug(string $pageKey, string $slug): DailyGame
    {
        $game = $this->gameBySlug($pageKey, $slug);

        return $this->dailyGameCacheService->getRecordByGameAndDate($pageKey, $game, now(), $this->cachedTTL);
    }

    public function games(Request $request, string $pageKey): LengthAwarePaginator
    {
        $fullKey = $pageKey;
        $fullKey .= ':'.CacheHelper::KEY_GAMES;

        return $this->gameCacheService->getRecords($fullKey, $request, $this->cachedTTL);
    }
}
