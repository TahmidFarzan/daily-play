<?php

namespace Tests\Feature;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Game;
use App\Models\GamePlay;
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
        return $this->service()->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game);
    }

    public function test_first_request_creates_a_game_play_for_the_current_calendar_date(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $gamePlay = $this->getFor($game);

        $this->assertSame(1, GamePlay::count());
        $this->assertSame($game->id, $gamePlay->game_id);
        $this->assertSame('2026-09-03', $gamePlay->date->format('Y-m-d'));
        $this->assertSame('00:00:00', $gamePlay->start_time->format('H:i:s'));
        $this->assertSame('23:59:59', $gamePlay->end_time->format('H:i:s'));
        $this->assertNotNull($gamePlay->board);
        $this->assertTrue($gamePlay->relationLoaded('game'));
        $this->assertTrue($gamePlay->relationLoaded('gameDifficulty'));
        $this->assertTrue($gamePlay->game->relationLoaded('logo'));
    }

    public function test_game_play_spans_the_full_calendar_day(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $gamePlay = $this->getFor($game);

        $this->assertSame('00:00:00', $gamePlay->start_time->format('H:i:s'));
        $this->assertSame('23:59:59', $gamePlay->end_time->format('H:i:s'));
        $this->assertSame('2026-09-03', $gamePlay->date->format('Y-m-d'));
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

        $cacheKey = CacheHelper::cacheKeyGenerateSingleGamePlayRecordByGameAndDatetime(
            CacheHelper::KEY_PLAY_GAME_PAGE,
            CacheHelper::KEY_GAME_PLAY,
            $game,
            now(),
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

    public function test_a_new_game_play_is_returned_when_the_calendar_date_changes(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->assertSame('2026-09-03', $first->date->format('Y-m-d'));

        $this->travelTo(Carbon::parse('2026-09-04 00:30:00'));

        $dayTwo = $this->getFor($game);

        $this->assertSame('2026-09-04', $dayTwo->date->format('Y-m-d'));
        $this->assertNotSame($first->id, $dayTwo->id);
        $this->assertSame(2, GamePlay::count());
    }

    public function test_game_play_is_active_at_both_inclusive_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $play = $this->getFor($game);

        $this->travelTo(Carbon::parse('2026-09-03 00:00:00'));

        $atStart = $this->getFor($game);

        $this->travelTo(Carbon::parse('2026-09-03 23:59:59'));

        $atEnd = $this->getFor($game);

        $this->assertSame($play->id, $atStart->id);
        $this->assertSame($play->id, $atEnd->id);
        $this->assertSame(1, GamePlay::count());
    }

    public function test_game_play_period_boundaries_are_inclusive_and_date_aware(): void
    {
        $game = $this->createZipGame();

        $service = $this->service();

        $atStart = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-10 00:00:00'));
        $during = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-10 12:00:00'));
        $atEnd = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-10 23:59:59'));

        $this->assertSame('2026-09-10', $atStart->date->format('Y-m-d'));
        $this->assertSame($atStart->id, $during->id);
        $this->assertSame($atStart->id, $atEnd->id);

        $beforeStart = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-09 23:59:59'));
        $afterEnd = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-11 00:00:00'));
        $differentDate = $service->getRecordByGameAndDatetime(CacheHelper::KEY_PLAY_GAME_PAGE, $game, Carbon::parse('2026-09-11 12:00:00'));

        $this->assertSame('2026-09-09', $beforeStart->date->format('Y-m-d'));
        $this->assertNotSame($atStart->id, $afterEnd->id);
        $this->assertSame($afterEnd->id, $differentDate->id);
        $this->assertSame(3, GamePlay::count());
    }

    public function test_after_end_time_a_new_game_play_is_created_for_the_next_day(): void
    {
        $this->travelTo(Carbon::parse('2026-09-03 17:55:00'));

        $game = $this->createZipGame();

        $first = $this->getFor($game);

        $this->travelTo(Carbon::parse('2026-09-04 17:55:01'));

        $second = $this->getFor($game);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('2026-09-04', $second->date->format('Y-m-d'));
        $this->assertSame('00:00:00', $second->start_time->format('H:i:s'));
        $this->assertSame('23:59:59', $second->end_time->format('H:i:s'));
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
