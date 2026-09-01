<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\DailyGame;
use App\Models\Game;
use App\Models\GameDifficulty;
use App\Services\GamePlay\ZipBoardGenerator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyGameCacheService
{
    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_DAILY_GAME;

    private string $secondKey = CacheHelper::KEY_DAILY_GAME;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_DAILY_GAME]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::KEY_DAILY_GAME]);
    }

    public function getRecordByGameAndDate(string $key, Game $game, ?CarbonInterface $date = null, ?int $cachedTTL = null): DailyGame
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $dateString = $date->format('Y-m-d');

        $cacheKey = CacheHelper::cacheKeyGenerateSingleDailyGameRecordByGameAndDate($key, $this->secondKey, $game, $dateString);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByGameAndDate($game, $dateString);

            if (! $record) {
                $record = $this->dbRecordCreate($game, $date);
            }

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    private function dbRecordByGameAndDate(Game $game, string $dateString, bool $orFail = false): ?DailyGame
    {
        $query = DailyGame::query()
            ->with(['game', 'game.logo', 'gameDifficulty'])
            ->where('game_id', $game->id)
            ->whereDate('game_date', $dateString);

        return $orFail ? $query->firstOrFail() : $query->first();
    }

    private function dbRecordCreate(Game $game, ?CarbonInterface $gameDate = null): DailyGame
    {
        $gameDate = ($gameDate ?? now())->copy()->startOfDay();
        $dateString = $gameDate->format('Y-m-d');

        $existing = $this->dbRecordByGameAndDate($game, $dateString);

        if ($existing) {
            return $existing;
        }

        $difficulty = GameDifficulty::query()->inRandomOrder()->first() ?? null;
        try {
            DB::transaction(function () use ($game, $gameDate, $dateString, $difficulty): void {
                if ($this->dbRecordByGameAndDate($game, $dateString)) {
                    return;
                }

                $board = [];

                switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate($game, $gameDate, $difficulty);
                        break;

                    default:
                        $board = [];
                        break;
                }

                DailyGame::create([
                    'game_id' => $game->id,
                    'game_difficulty_id' => $difficulty->id ?? null,
                    'game_date' => $dateString,
                    'board' => $board,
                    'starts_at' => $gameDate->copy()->startOfDay(),
                    'ends_at' => $gameDate->copy()->endOfDay(),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Unable to create the daily game.', [
                'exception' => $exception,
            ]);
        }

        return $this->dbRecordByGameAndDate($game, $dateString, orFail: true);
    }
}
