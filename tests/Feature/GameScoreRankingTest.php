<?php

namespace Tests\Feature;

use App\Helpers\CacheHelper;
use App\Models\Game;
use App\Models\GameScore;
use App\Models\Player;
use App\Models\User;
use App\Services\Cache\GameChallengeCacheService;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameScoreRankingTest extends TestCase
{
    use RefreshDatabase;

    protected Game $game;

    protected int $gameChallengeId;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->state([
            'name' => 'Default Admin',
            'is_super_admin' => true,
            'created_by_id' => null,
        ])->create();

        $this->seed(GameDifficultySeeder::class);

        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $this->game = Game::factory()->state(['name' => 'Zip'])->create();

        $service = app(GameChallengeCacheService::class);
        $challenge = $service->getRecordByGameAndDate(
            CacheHelper::KEY_PLAY_GAME_PAGE,
            $this->game,
            now(),
            300,
        );

        $this->gameChallengeId = $challenge->id;
    }

    protected function createPlayer(string $name = 'Player'): Player
    {
        $index = Player::count() + 1;

        return Player::create([
            'name' => $name,
            'email' => "player{$index}@example.com",
        ]);
    }

    protected function submitScore(Player $player, int $durationMs, int $backtracks)
    {
        return $this->postJson(route('games.score.save', ['slug' => $this->game->slug]), [
            'player_id' => $player->id,
            'duration_ms' => $durationMs,
            'backtracks' => $backtracks,
        ])->assertOk()->json();
    }

    protected function ranksOf(array $scores = []): array
    {
        $ranks = [];

        foreach ($scores as [$durationMs, $backtracks]) {
            $player = $this->createPlayer();
            $result = $this->submitScore($player, $durationMs, $backtracks);
            $ranks[] = $result['data']['rank'];
        }

        return $ranks;
    }

    public function test_ranking_orders_by_duration_ms_asc(): void
    {
        $this->assertSame([1, 2], $this->ranksOf([
            [10123, 0],
            [10124, 0],
        ]));
    }

    public function test_exact_tie_shares_the_same_rank(): void
    {
        $this->assertSame([1, 1], $this->ranksOf([
            [70587, 0],
            [70587, 0],
        ]));
    }

    public function test_tie_followed_by_another_player_skips_to_rank_three(): void
    {
        $this->assertSame([1, 1, 3], $this->ranksOf([
            [70587, 0],
            [70587, 0],
            [70600, 0],
        ]));
    }

    public function test_backtracks_break_a_duration_tie(): void
    {
        $this->assertSame([1, 2], $this->ranksOf([
            [70587, 0],
            [70587, 1],
        ]));
    }

    public function test_three_way_tie_keeps_rank_one_for_all(): void
    {
        $this->assertSame([1, 1, 1, 4], $this->ranksOf([
            [70587, 0],
            [70587, 0],
            [70587, 0],
            [70600, 0],
        ]));
    }

    public function test_multiple_ties_use_standard_competition_ranking(): void
    {
        $this->assertSame([1, 1, 3, 3, 5], $this->ranksOf([
            [70587, 0],
            [70587, 0],
            [70590, 0],
            [70590, 0],
            [70600, 0],
        ]));
    }

    public function test_score_is_created_with_exact_fields(): void
    {
        $player = $this->createPlayer();

        $result = $this->submitScore($player, 70587, 0);

        $this->assertSame('success', $result['status']);

        $score = GameScore::where('game_challenge_id', $this->gameChallengeId)
            ->where('player_id', $player->id)
            ->firstOrFail();

        $this->assertSame(70587, $score->duration_ms);
        $this->assertSame(0, $score->backtracks);
        $this->assertSame($this->gameChallengeId, $score->game_challenge_id);
        $this->assertSame($player->id, $score->player_id);
        $this->assertNotEmpty($score->slug);
    }

    public function test_duplicate_submission_does_not_create_a_second_score(): void
    {
        $player = $this->createPlayer();

        $this->submitScore($player, 70587, 0);
        $this->submitScore($player, 70000, 2);

        $this->assertSame(1, GameScore::where('player_id', $player->id)->count());
        $this->assertSame(70587, GameScore::where('player_id', $player->id)->firstOrFail()->duration_ms);
    }

    public function test_rank_and_top_five_are_returned_from_the_backend(): void
    {
        $playerA = $this->createPlayer('Player A');
        $playerB = $this->createPlayer('Player B');
        $playerC = $this->createPlayer('Player C');

        $this->submitScore($playerA, 70587, 0);
        $this->submitScore($playerB, 70587, 0);
        $result = $this->submitScore($playerC, 70600, 0);

        $this->assertSame(3, $result['data']['rank']);

        $top = $result['data']['top_rankers'];

        $this->assertCount(3, $top);
        $this->assertSame([1, 1, 3], array_column($top, 'rank'));
        $this->assertSame([70587, 70587, 70600], array_column($top, 'duration_ms'));
        $this->assertSame('Player C', $top[2]['player']['name']);
    }

    public function test_player_rank_is_returned_even_when_outside_top_five(): void
    {
        foreach (range(1, 6) as $i) {
            $this->submitScore($this->createPlayer('Slow' . $i), 60000 + $i, 0);
        }

        $fast = $this->createPlayer('Fast');
        $result = $this->submitScore($fast, 59000, 0);

        $this->assertSame(1, $result['data']['rank']);
        $this->assertCount(5, $result['data']['top_rankers']);
    }

    public function test_different_day_challenges_do_not_affect_each_others_ranking(): void
    {
        $this->submitScore($this->createPlayer(), 50000, 0);

        $this->travelTo(Carbon::parse('2026-09-02 09:00:00'));

        $service = app(GameChallengeCacheService::class);
        $dayTwo = $service->getRecordByGameAndDate(
            CacheHelper::KEY_PLAY_GAME_PAGE,
            $this->game,
            now(),
            300,
        );

        $player = $this->createPlayer();
        $result = $this->postJson(route('games.score.save', ['slug' => $this->game->slug]), [
            'player_id' => $player->id,
            'duration_ms' => 90000,
            'backtracks' => 0,
        ])->assertOk()->json();

        $this->assertSame(1, $result['data']['rank']);
        $this->assertSame(1, GameScore::where('game_challenge_id', $dayTwo->id)->count());
    }

    public function test_score_requires_valid_device_fields_from_request(): void
    {
        $player = $this->createPlayer();

        $result = $this->submitScore($player, 12345, 1);

        $score = GameScore::firstOrFail();
        $this->assertSame($player->id, $score->player_id);
        $this->assertSame(12345, $score->duration_ms);
        $this->assertSame(1, $score->backtracks);
        $this->assertNotNull($score->device);
    }
}
