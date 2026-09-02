<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Models\Player;
use App\Models\GameChallenge;
use App\Models\Game;
use App\Services\Cache\GameChallengeCacheService;
use App\Services\Cache\GameCacheService;
use Illuminate\Http\Request;
use App\Http\Requests\PlayerRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PageService
{
    protected int $cachedTTL = 300;

    protected GameCacheService $gameCacheService;

    protected GameChallengeCacheService $gameChallengeCacheService;

    public function __construct(GameCacheService $gameCacheService, GameChallengeCacheService $gameChallengeCacheService)
    {
        $this->gameCacheService = $gameCacheService;
        $this->gameChallengeCacheService = $gameChallengeCacheService;
    }

    public function gameBySlug(string $pageKey, string $slug): Game
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAME;

        return $this->gameCacheService->getRecordBySlug($fullKey, $slug, $this->cachedTTL);
    }

    public function gameChallengeByGameSlug(string $pageKey, string $slug): GameChallenge
    {
        $game = $this->gameBySlug($pageKey, $slug);

        return $this->gameChallengeCacheService->getRecordByGameAndDate($pageKey, $game, now(), $this->cachedTTL);
    }

    public function games(Request $request, string $pageKey): LengthAwarePaginator
    {
        $fullKey = $pageKey;
        $fullKey .= ':' . CacheHelper::KEY_GAMES;

        return $this->gameCacheService->getRecords($fullKey, $request, $this->cachedTTL);
    }

    public function playerGet(string $slug)
    {
        try {

            $query = Player::where('slug', $slug)
                ->orWhere('email', $slug)->orWhere('mobile', $slug);

            $player = $query->first();

            if (!$player) {
                return [
                    'status' => 'error',
                    'message' => 'Player not found.',
                ];
            }

            return [
                'status' => 'success',
                'data' => $player,
            ];
        } catch (Exception $exception) {
            Log::error('Player get failed.', [
                'exception' => $exception,
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to get player. Please try again.',
            ];
        }
    }

    public function playerSave(PlayerRequest $request)
    {
        try {
            $player = DB::transaction(function () use ($request) {
                $slug = $request->input('slug');
                $email = $request->input('email');
                $mobile = $request->input('mobile');

                $player = $slug ? Player::where('slug', $slug)->first() : null;

                if (! $player && ($email || $mobile)) {
                    $query = Player::query();

                    if ($email) {
                        $query->where('email', $email);
                    }

                    if ($mobile) {
                        $query->orWhere('mobile', $mobile);
                    }

                    $player = $query->first();
                }

                if (! $player) {
                    $player = new Player();

                    $player->name = $request->input('name');
                    $player->email = $email;
                    $player->mobile = $mobile;
                    $player->address = $request->input('address');

                    $player->save();

                    return $player;
                }

                $player->name = $request->input('name');
                $player->email = $email;
                $player->mobile = $mobile;
                $player->address = $request->input('address');

                if ($player->isDirty()) {
                    $player->save();
                }

                return $player;
            });

            return [
                'status' => 'success',
                'data' => $player,
            ];
        } catch (Exception $exception) {
            Log::error('Player save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to save user. Please try again.',
            ];
        }
    }
}
