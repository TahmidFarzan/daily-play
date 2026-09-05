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

    public function getRecordByGameAndDatetime(string $key, Game $game, ?CarbonInterface $datetime = null, ?int $cachedTTL = null): GamePlay
    {
        $datetime = $datetime ?? now();

        $cacheKey = CacheHelper::cacheKeyGenerateSingleGamePlayRecordByGameAndDatetime($key, $this->secondKey, $game, $datetime);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByGameAndDatetime($game, $datetime);

            if (! $record) {
                $record = $this->dbRecordCreate($game, $datetime);
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

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('start_at', '<=', $date);
        }

        return $query->orderByDesc('id')->paginate($this->getPerPage($request->input("per_page", $perPage)))->appends($request->all());
    }

    private function dbRecordByGameAndDatetime(Game $game, CarbonInterface $datetime, bool $orFail = false): ?GamePlay
    {
        $query = GamePlay::query()
            ->with(['game', 'game.logo', 'gameDifficulty'])
            ->where('game_id', $game->id)
            ->where('start_at', '<=', $datetime)
            ->where('end_at', '>=', $datetime);

        return $orFail ? $query->firstOrFail() : $query->first();
    }

    private function dbRecordCreate(Game $game, ?CarbonInterface $startAt = null): GamePlay
    {
        $startAt = ($startAt ?? now())->copy();
        $endAt = $startAt->copy()->addHours(24);

        $existing = $this->dbRecordByGameAndDatetime($game, $startAt);

        if ($existing) {
            return $existing;
        }

        $difficulty = GameDifficulty::query()->inRandomOrder()->first() ?? null;
        try {
            DB::transaction(function () use ($game, $startAt, $endAt, $difficulty): void {
                if ($this->dbRecordByGameAndDatetime($game, $startAt)) {
                    return;
                }

                $board = [];

                switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate($game, $startAt, $difficulty);
                        break;

                    default:
                        $board = [];
                        break;
                }

                GamePlay::create([
                    'game_id' => $game->id,
                    'game_difficulty_id' => $difficulty->id ?? null,
                    'board' => $board,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Unable to create the game play.', [
                'exception' => $exception,
            ]);
        }

        return $this->dbRecordByGameAndDatetime($game, $startAt, orFail: true);
    }
}
