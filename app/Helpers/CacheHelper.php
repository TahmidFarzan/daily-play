<?php

namespace App\Helpers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CacheHelper
{
    public const KEY_RECORD_LIMIT = 'record-limit';

    public const KEY_PAGE = 'page';

    public const KEY_HOME_PAGE = 'home-page';
    public const KEY_PLAY_GAME_PAGE = 'play-game-page';
    public const KEY_GAME_DETAILS_PAGE = 'game-details-page';

    public const KEY_GAME = 'game';
    public const KEY_GAMES = 'games';

    public const KEY_GAME_CHALLENGE = 'game-challenge';

    public const KEY_CURSOR = 'cursor';
    public const KEY_PER_PAGE = 'per-page';

    public const KEY_BY_SLUG = 'by-slug';
    public const KEY_BY_ID = 'by-id';
    public const KEY_BY_DATE = 'by-date';
    public const KEY_BY_GAME_SLUG = 'by-game-slug';

    public const KEY_LAST_PAGE_NO = 'last-page-no';

    public const TAG_GAME = 'game';
     public const TAG_GAME_CHALLENGE = 'game-challenge';


    public static function cacheKeyGenerateSingleRecordBySlug(string $key, string $secondKey, string $slug): string
    {
        $cacheKey = "{$key}:{$secondKey}:";
        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";
        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordById(string $key, string $secondKey, int $id): string
    {
        $cacheKey = "{$key}:{$secondKey}:";
        $cacheKey .= ':' . CacheHelper::KEY_BY_ID . ":{$id}";
        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordByLimit(string $key, string $secondKey, ?Request $request = null, int $limit = 4): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        $cacheKey .= ':' . CacheHelper::KEY_RECORD_LIMIT . ":{$limit}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordsRequest(string $key, string $secondKey, Request $request, int | null $perPage = null): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            if ($perPage !== null && !$request->has('per_page')) {
                $cacheData['per_page'] = $perPage;
            }

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }
        else{
            if($perPage){
                $cacheKey .= ':'.self::KEY_PER_PAGE."={$perPage}";
            }
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForLastPageNo(string $key, string $secondKey): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_LAST_PAGE_NO;

        return $cacheKey;
    }


    // GameChallenge
    public static function cacheKeyGenerateSingleGameChallengeRecordByGameAndDate(string $key, string $secondKey, Game $game, $date): string
    {
        $cacheKey = "{$key}:{$secondKey}:";
        $cacheKey .=  CacheHelper::KEY_GAME_CHALLENGE;
        $cacheKey .= ':' . CacheHelper::KEY_BY_GAME_SLUG . ":{$game->slug}";
        $cacheKey .= ':' . CacheHelper::KEY_BY_ID . ":{$date}";
        return $cacheKey;
    }

}
