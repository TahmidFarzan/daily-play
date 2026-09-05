<?php

namespace Tests\Feature;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\GamePlay;
use App\Models\Game;
use App\Models\User;
use App\Services\Cache\GamePlayCacheService;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GamePlayCacheServiceTest extends TestCase
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

    protected function service(): GamePlayCacheService
    {
        return app(GamePlayCacheService::class);
    }

    protected function getFor(Game $game): GamePlay
    {
        return $this->service()->getRecordByGame(CacheHelper::KEY_PLAY_GAME_PAGE, $game);
    }

    public function test_first_request_preserves_the_exact_start_datetime_and_sets_end_24_hours_later(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $gamePlay = $this->getFor($game);

        $this->assertSame(1, GamePlay::count());
        $this->assertSame($game->id, $gamePlay->game_id);
        $this->assertSame('2026-09-03 17:55:00', $gamePlay->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-04 17:55:00', $gamePlay->end_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-03 17:55:00', $gamePlay->end_at->copy()->subHours(24)->format('Y-m-d H:i:s'));
        $this->assertNotNull($gamePlay->board);
        $this->assertTrue($gamePlay->relationLoaded('game'));
        $this->assertTrue($gamePlay->relationLoaded('gameDifficulty'));
        $this->assertTrue($gamePlay->game->relationLoaded('logo'));
    }

    public function test_start_at_is_not_forced_to_the_beginning_of_the_day(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $gamePlay = $this->getFor($game);

        $this->assertSame('17:55:00', $gamePlay->start_at->format('H:i:s'));
        $this->assertSame('17:55:00', $gamePlay->end_at->format('H:i:s'));
    }

    public function test_second_request_returns_the_same_game_play_without_regenerating(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->assertSame(1, GamePlay::count());

        DB::enableQueryLog();
        DB::flushQueryLog();

        $second = $this->getFor($game);

        $queries = DB::getQueryLog();
        $inserts = array_filter($queries, fn (array $query): bool => preg_match('/^insert/i', $query['query']) === 1);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->board, $second->board);
        $this->assertSame($first->game_difficulty_id, $second->game_difficulty_id);
        $this->assertSame(1, GamePlay::count());
        $this->assertEmpty($inserts, 'No insert query should run.');
    }

    public function test_cache_hit_returns_the_same_game_play_without_a_database_query(): void
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
        $this->assertSame(1, GamePlay::count());
    }

    public function test_cache_miss_with_existing_game_play_returns_the_database_record_without_a_new_board(): void
    {
        Cache::flush();
        config(['cache.cache_enable' => true]);

        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $cached = $this->getFor($game);

        $cacheKey = CacheHelper::cacheKeyGenerateSingleGamePlayRecordByGame(
            CacheHelper::KEY_PLAY_GAME_PAGE,
            CacheHelper::KEY_GAME_PLAY,
            $game,
        );

        CacheServerHelper::clearCached($cacheKey, [
            CacheHelper::KEY_PLAY_GAME_PAGE,
            CacheHelper::TAG_GAME_PLAY,
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $fromDatabase = $this->getFor($game);

        $queries = DB::getQueryLog();
        $inserts = array_filter($queries, fn (array $query): bool => preg_match('/^insert/i', $query['query']) === 1);

        $this->assertSame($cached->id, $fromDatabase->id);
        $this->assertSame($cached->board, $fromDatabase->board);
        $this->assertSame($cached->game_difficulty_id, $fromDatabase->game_difficulty_id);
        $this->assertSame(1, GamePlay::count());
        $this->assertEmpty($inserts, 'No insert query should run.');
    }

    public function test_game_play_remains_active_across_midnight_and_keeps_the_same_board(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->assertSame('2026-09-04 17:55:00', $first->end_at->format('Y-m-d H:i:s'));

        $this->travelTo(Carbon::parse('2026-09-04 00:30:00'));

        $afterMidnight = $this->getFor($game);

        $this->assertSame($first->id, $afterMidnight->id);
        $this->assertSame($first->board, $afterMidnight->board);
        $this->assertSame(1, GamePlay::count());
    }

    public function test_game_play_is_still_active_at_the_inclusive_end_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->travelTo(Carbon::parse('2026-09-04 17:55:00'));

        $atBoundary = $this->getFor($game);

        $this->assertSame($first->id, $atBoundary->id);
        $this->assertSame(1, GamePlay::count());
    }

    public function test_after_end_at_a_new_game_play_is_created(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->travelTo(Carbon::parse('2026-09-04 17:55:01'));

        $second = $this->getFor($game);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('2026-09-04 17:55:01', $second->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-05 17:55:01', $second->end_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, GamePlay::count());
    }

    public function test_difficulty_stays_stable_for_the_game_play(): void
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
