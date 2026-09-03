<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Models\Game;
use App\Services\Cache\GameCacheService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GameService
{
    protected int $cachedTTL = 300;

    protected GameCacheService $gameCacheService;

    public function __construct(GameCacheService $gameCacheService)
    {
        $this->gameCacheService = $gameCacheService;
    }

    public function findBySlug(string $pageKey, string $slug): Game
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAME;

        return $this->gameCacheService->getRecordBySlug($fullKey, $slug, $this->cachedTTL);
    }

    public function search(Request $request, string $pageKey): LengthAwarePaginator
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAMES;

        return $this->gameCacheService->getRecords($fullKey, $request, $this->cachedTTL, 15);
    }
}
