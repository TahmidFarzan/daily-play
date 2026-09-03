<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\GamePlay;
use App\Models\Game;
use App\Models\GameDifficulty;
use App\Services\GamePlay\ZipBoardGenerator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GamePlayCacheService
{
    private int $perPage   = 5000;
    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_GAME_PLAY;

    private string $secondKey = CacheHelper::KEY_GAME_PLAY;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_GAME_PLAY]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::KEY_GAME_PLAY]);
    }

    public function getRecordByGameAndDate(string $key, Game $game, ?CarbonInterface $date = null, ?int $cachedTTL = null): GamePlay
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $dateString = $date->format('Y-m-d');

        $cacheKey = CacheHelper::cacheKeyGenerateSingleGamePlayRecordByGameAndDate($key, $this->secondKey, $game, $dateString);

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

    public function searchRecordsByGame(string $key, Game $game, Request $request, int | null $perPage = null, ?int $cachedTTL = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? $this->perPage;
        $cacheKey = CacheHelper::cacheKeyGenerateGamePlayRecordsByGame($key, $this->secondKey, $game, $request, $perPage);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbRecordsByGame($game, $request, $perPage);


            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    private function getPerPage(int | null $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbRecordsByGame(Game $game, Request $request, int | null $perPage = null ): LengthAwarePaginator
    {
        $request ??= request();

        $query = GamePlay::with(['gameDifficulty'])->where('game_id', $game->id);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'game_date',
                'slug',
            ], 'like', $likeSearch);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('game_date', '<=', $date);
        }

        return $query->orderByDesc('id')->paginate($this->getPerPage($request->input("per_page", $perPage)))->appends($request->all());
    }

    private function dbRecordByGameAndDate(Game $game, string $dateString, bool $orFail = false): ?GamePlay
    {
        $query = GamePlay::query()
            ->with(['game', 'game.logo', 'gameDifficulty'])
            ->where('game_id', $game->id)
            ->whereDate('game_date', $dateString);

        return $orFail ? $query->firstOrFail() : $query->first();
    }

    private function dbRecordCreate(Game $game, ?CarbonInterface $gameDate = null): GamePlay
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

                GamePlay::create([
                    'game_id' => $game->id,
                    'game_difficulty_id' => $difficulty->id ?? null,
                    'game_date' => $dateString,
                    'board' => $board,
                    'starts_at' => $gameDate->copy()->startOfDay(),
                    'ends_at' => $gameDate->copy()->endOfDay(),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Unable to create the game play.', [
                'exception' => $exception,
            ]);
        }

        return $this->dbRecordByGameAndDate($game, $dateString, orFail: true);
    }
}
