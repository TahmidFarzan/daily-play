<?php
namespace App\Helpers;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CacheServerHelper
{
    const oneDayInSecond   = 86400;
    const oneHourInSecond   = 3600;
    const sixHoursInSecond = 21600;
    const threeMinInSecond = 180;


    protected static function driver(): string
    {
        return config('cache.default');
    }

    protected static function supportsTags(): bool
    {
        return ! in_array(self::driver(), ['file', 'array'], true);
    }

    protected static function resolveLifeTime(?int $lifeTime = null): int
    {
        return ! empty($lifeTime) && $lifeTime > 0
            ? $lifeTime
            : self::oneDayInSecond;
    }

    protected static function ttl(?int $lifeTime = null): DateTimeInterface
    {
        return Carbon::now()->addSeconds(self::resolveLifeTime($lifeTime));
    }

    public static function isConnected(): bool
    {
        if (! config('cache.enable') || ! config('cache.default')) {
            return false;
        }

        try {
            Cache::put('__cache_test__', true, self::ttl(2));
            Cache::forget('__cache_test__');

            return true;
        } catch (Throwable $ex) {
            Log::error('Cache store not available: ' . $ex->getMessage());

            return false;
        }
    }

    public static function keyGenerate(string $key): string
    {
        return Str::lower(Str::slug($key));
    }

    public static function tagsFormat(string | array $key): string | array
    {
        if (is_string($key)) {
            return Str::lower(Str::slug($key));
        }

        return array_map(function ($item) {
            return Str::lower(Str::slug($item));
        }, $key);
    }

    public static function cachedData(
        string $key,
        mixed $data,
        ?int $lifeTime = null,
        array $tags = []
    ): void {
        if (! self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);
        $formatedTags = self::tagsFormat($tags);

        try {
            $store = (! empty($formatedTags) && self::supportsTags())
                ? Cache::tags($formatedTags)
                : Cache::store();

            $store->put($key, $data, self::ttl($lifeTime));
        } catch (Throwable $ex) {
            Log::error("Failed to cache '{$key}': " . $ex->getMessage());
        }
    }

    public static function getCachedData(
        string $key,
        array $tags = []
    ): mixed {
        if (! self::isConnected()) {
            return null;
        }

        $key = self::keyGenerate($key);
        $formatedTags = self::tagsFormat($tags);

        try {
            $store = (! empty($formatedTags) && self::supportsTags())
                ? Cache::tags($formatedTags)
                : Cache::store();

            return $store->get($key);
        } catch (Throwable $ex) {
            Log::error("Failed to retrieve cached '{$key}': " . $ex->getMessage());

            return null;
        }
    }

    public static function clearCached(string $key, array $tags = []): void
    {
        if (! self::isConnected()) {
            return;
        }

        $key = self::keyGenerate($key);

        try {
            $store = (! empty($tags) && self::supportsTags())
                ? Cache::tags($tags)
                : Cache::store();

            $store->forget($key);
        } catch (Throwable $ex) {
            Log::error("Failed to clear cached '{$key}': " . $ex->getMessage());
        }
    }

    public static function clearCachedByTag(string | array $tags): void
    {
        if (! self::isConnected() || ! self::supportsTags()) {
            return;
        }

        try {
            Cache::tags((array) $tags)->flush();
        } catch (Throwable $ex) {
            Log::error('Cache tag flush failed: ' . $ex->getMessage());
        }
    }

    public static function clearAllCached(): void
    {
        if (! self::isConnected()) {
            return;
        }

        try {
            Cache::flush();
        } catch (Throwable $ex) {
            Log::error('Cache flush failed: ' . $ex->getMessage());
        }
    }
}
