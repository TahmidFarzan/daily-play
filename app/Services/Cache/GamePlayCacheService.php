<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\GamePlay;
use App\Services\GamePlay\ZipBoardGenerator;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GamePlayCacheService
{
    private int $perPage = 5000;

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

    public function searchRecordsByGame(string $key, Game $game, Request $request, ?int $perPage = null, ?int $cachedTTL = null): LengthAwarePaginator
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

    private function getPerPage(?int $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbRecordsByGame(Game $game, Request $request, ?int $perPage = null): LengthAwarePaginator
    {
        $request ??= request();

        $query = GamePlay::with(['gameDifficulty'])->where('game_id', $game->id);

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('date', '<=', $date);
        }

        return $query->orderByDesc('id')->paginate($this->getPerPage($request->input('per_page', $perPage)))->appends($request->all());
    }

    private function dbRecordByGameAndDatetime(Game $game, CarbonInterface $datetime, bool $orFail = false): ?GamePlay
    {
        $date = $datetime->toDateString();
        $time = $datetime->format('H:i:s');

        $query = GamePlay::query()
            ->with(['game', 'game.logo', 'gameDifficulty'])
            ->where('game_id', $game->id)
            ->where(function ($query) use ($date, $time) {
                $query->whereDate('date', '<', $date)
                    ->orWhere(function ($query) use ($date, $time) {
                        $query->whereDate('date', '=', $date)
                            ->where('start_time', '<=', $time);
                    });
            })
            ->where(function ($query) use ($date, $time) {
                $query->whereDate('date', '>', $date)
                    ->orWhere(function ($query) use ($date, $time) {
                        $query->whereDate('date', '=', $date)
                            ->where('end_time', '>=', $time);
                    });
            });

        return $orFail ? $query->firstOrFail() : $query->first();
    }

    private function dbRecordCreate(Game $game, ?CarbonInterface $datetime = null): GamePlay
    {
        $datetime = ($datetime ?? now())->copy();
        $date = $datetime->toDateString();

        $existing = $this->dbRecordByGameAndDatetime($game, $datetime);

        if ($existing) {
            return $existing;
        }

        $difficulty = GameDifficulty::query()->inRandomOrder()->first() ?? null;
        try {
            DB::transaction(function () use ($game, $datetime, $date, $difficulty): void {
                if ($this->dbRecordByGameAndDatetime($game, $datetime)) {
                    return;
                }

                $board = [];

                switch ($game->slug) {
                    case 'zip':
                        $board = app(ZipBoardGenerator::class)->generate($game, $datetime, $difficulty);
                        break;

                    default:
                        $board = [];
                        break;
                }

                GamePlay::create([
                    'game_id' => $game->id,
                    'game_difficulty_id' => $difficulty->id ?? null,
                    'board' => $board,
                    'date' => $date,
                    'start_time' => $datetime->copy()->startOfDay()->format('H:i:s'),
                    'end_time' => $datetime->copy()->endOfDay()->format('H:i:s'),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Unable to create the game play.', [
                'exception' => $exception,
            ]);
        }

        return $this->dbRecordByGameAndDatetime($game, $datetime, orFail: true);
    }
}
