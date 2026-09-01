<?php

namespace Tests\Feature;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\DailyGame;
use App\Models\Game;
use App\Models\User;
use App\Services\Cache\DailyGameCacheService;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyGameCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->state([
            'name' => 'Default Admin',
            'is_super_admin' => true,
            'created_by_id' => null,
        ])->create();

        $this->seed(GameDifficultySeeder::class);
    }

    protected function tearDown(): void
    {
        config(['cache.cache_enable' => false]);
        Cache::flush();

        parent::tearDown();
    }

    protected function createZipGame(): Game
    {
        return Game::factory()->state(['name' => 'Zip'])->create();
    }

    protected function service(): DailyGameCacheService
    {
        return app(DailyGameCacheService::class);
    }

    protected function getFor(Game $game): DailyGame
    {
        return $this->service()->getRecordByGameAndDate(CacheHelper::KEY_PLAY_GAME_PAGE, $game);
    }

    public function test_first_request_generates_saves_and_returns_the_daily_game(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $dailyGame = $this->getFor($game);

        $this->assertSame(1, DailyGame::count());
        $this->assertSame($game->id, $dailyGame->game_id);
        $this->assertSame('2026-09-01', $dailyGame->game_date->toDateString());
        $this->assertNotNull($dailyGame->board);
        $this->assertNotNull($dailyGame->starts_at);
        $this->assertNotNull($dailyGame->ends_at);
        $this->assertTrue($dailyGame->relationLoaded('game'));
        $this->assertTrue($dailyGame->relationLoaded('gameDifficulty'));
        $this->assertTrue($dailyGame->game->relationLoaded('logo'));
    }

    public function test_second_request_returns_the_same_daily_game_without_regenerating(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->assertSame(1, DailyGame::count());

        DB::enableQueryLog();
        DB::flushQueryLog();

        $second = $this->getFor($game);

        $queries = DB::getQueryLog();
        $inserts = array_filter($queries, fn (array $query): bool => preg_match('/^insert/i', $query['query']) === 1);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->board, $second->board);
        $this->assertSame($first->game_difficulty_id, $second->game_difficulty_id);
        $this->assertSame(1, DailyGame::count());
        $this->assertEmpty($inserts, 'No insert query should run.');
    }

    public function test_cache_hit_returns_the_same_daily_game_without_a_database_query(): void
    {
        Cache::flush();
        config(['cache.cache_enable' => true]);

        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $second = $this->getFor($game);

        $queries = DB::getQueryLog();

        $this->assertSame([], $queries);
        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->board, $second->board);
        $this->assertSame($first->game_difficulty_id, $second->game_difficulty_id);
        $this->assertSame(1, DailyGame::count());
    }

    public function test_cache_miss_with_existing_daily_game_returns_the_database_record_without_a_new_board(): void
    {
        Cache::flush();
        config(['cache.cache_enable' => true]);

        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $cached = $this->getFor($game);

        $cacheKey = CacheHelper::cacheKeyGenerateSingleDailyGameRecordByGameAndDate(
            CacheHelper::KEY_PLAY_GAME_PAGE,
            CacheHelper::KEY_DAILY_GAME,
            $game,
            '2026-09-01',
        );

        CacheServerHelper::clearCached($cacheKey, [
            CacheHelper::KEY_PLAY_GAME_PAGE,
            CacheHelper::TAG_DAILY_GAME,
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $fromDatabase = $this->getFor($game);

        $queries = DB::getQueryLog();
        $inserts = array_filter($queries, fn (array $query): bool => preg_match('/^insert/i', $query['query']) === 1);

        $this->assertSame($cached->id, $fromDatabase->id);
        $this->assertSame($cached->board, $fromDatabase->board);
        $this->assertSame($cached->game_difficulty_id, $fromDatabase->game_difficulty_id);
        $this->assertSame(1, DailyGame::count());
        $this->assertEmpty($inserts, 'No insert query should run.');
    }

    public function test_new_day_creates_a_new_daily_game_while_the_same_day_keeps_its_own(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 12:00:00'));

        $game = $this->createZipGame();

        $dayOne = $this->getFor($game);
        $sameDay = $this->getFor($game);

        $this->assertSame($dayOne->id, $sameDay->id);
        $this->assertSame($dayOne->board, $sameDay->board);

        $this->travelTo(Carbon::parse('2026-09-02 12:00:00'));

        $dayTwo = $this->getFor($game);

        $this->assertNotSame($dayOne->id, $dayTwo->id);
        $this->assertNotSame($dayOne->board, $dayTwo->board);
        $this->assertSame('2026-09-02', $dayTwo->game_date->toDateString());
        $this->assertSame(2, DailyGame::count());
    }

    public function test_difficulty_stays_stable_for_the_daily_game(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->assertNotNull($first->game_difficulty_id);
        $this->assertContains($first->gameDifficulty->slug, ['easy', 'normal', 'hard']);

        $second = $this->getFor($game);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->game_difficulty_id, $second->game_difficulty_id);
        $this->assertSame($first->gameDifficulty->slug, $second->gameDifficulty->slug);
    }
}
