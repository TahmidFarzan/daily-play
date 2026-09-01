<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use Exception;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Cache\GameCacheService;
use Illuminate\Pagination\LengthAwarePaginator;

class PageService
{
    protected int $cachedTTL = 300;

    protected GameCacheService $gameCacheService;


    public function __construct( GameCacheService $gameCacheService) {
        $this->gameCacheService    = $gameCacheService;
    }

    public function gameBySlug(string $pageKey, string $slug): Game
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAME;
        return $this->gameCacheService->getRecordBySlug($fullKey, $slug, $this->cachedTTL);
    }

    public function games(Request $request, string $pageKey): LengthAwarePaginator
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAMES;
        return $this->gameCacheService->getRecords($fullKey, $request, $this->cachedTTL);
    }
}
