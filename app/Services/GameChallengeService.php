<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameChallenge;
use App\Services\Cache\GameChallengeCacheService;

class GameChallengeService
{
    protected int $cachedTTL = 300;

    protected GameChallengeCacheService $gameChallengeCacheService;

    public function __construct(GameChallengeCacheService $gameChallengeCacheService)
    {
        $this->gameChallengeCacheService = $gameChallengeCacheService;
    }

    public function findByGameSlug(string $pageKey, Game $game): GameChallenge
    {
        return $this->gameChallengeCacheService->getRecordByGameAndDate($pageKey, $game, now(), $this->cachedTTL);
    }
}
