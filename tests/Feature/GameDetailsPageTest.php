<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\GamePlay;
use App\Models\User;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameDetailsPageTest extends TestCase
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

    protected function createZipGame(): Game
    {
        return Game::factory()->state(['name' => 'Zip'])->create();
    }

    protected function createGamePlay(Game $game, string $date): void
    {
        $startAt = Carbon::parse($date);

        GamePlay::create([
            'game_id' => $game->id,
            'game_difficulty_id' => GameDifficulty::query()->value('id'),
            'board' => [],
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addHours(24),
        ]);
    }

    public function test_details_route_returns_game_and_paginated_game_plays(): void
    {
        $game = $this->createZipGame();

        $this->createGamePlay($game, '2026-09-01');
        $this->createGamePlay($game, '2026-09-02');

        $response = $this->get(route('games.details', ['slug' => $game->slug]));

        $response->assertSuccessful();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('games/Details')
            ->where('game.id', $game->id)
            ->where('game.slug', $game->slug)
            ->has('gamePlays.data', 2)
            ->where('gamePlays.total', 2)
            ->where('gamePlays.per_page', 5000)
            ->has('gamePlays.data.0')
            ->has('gamePlays.data.0.id')
            ->has('gamePlays.data.0.start_at')
            ->has('gamePlays.data.0.end_at')
        );
    }

    public function test_details_date_filter_filters_game_plays(): void
    {
        $game = $this->createZipGame();

        $this->createGamePlay($game, '2026-09-01');
        $this->createGamePlay($game, '2026-09-02');
        $this->createGamePlay($game, '2026-09-03');

        $response = $this->get(route('games.details', ['slug' => $game->slug, 'date' => '2026-09-02']));

        $response->assertSuccessful();

        $response->assertInertia(fn (Assert $page) => $page
            ->has('gamePlays.data', 2)
            ->where('gamePlays.total', 2)
        );
    }

    public function test_details_search_filters_game_plays_by_date(): void
    {
        $game = $this->createZipGame();

        $this->createGamePlay($game, '2026-09-01');
        $this->createGamePlay($game, '2026-09-02');
        $this->createGamePlay($game, '2026-09-03');

        $response = $this->get(route('games.details', ['slug' => $game->slug, 'search' => '2026-09-03']));

        $response->assertSuccessful();

        $response->assertInertia(fn (Assert $page) => $page
            ->has('gamePlays.data', 1)
            ->where('gamePlays.total', 1)
        );
    }

    public function test_details_per_page_filter_is_respected(): void
    {
        $game = $this->createZipGame();

        foreach (range(1, 15) as $day) {
            $this->createGamePlay($game, '2026-09-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->get(route('games.details', ['slug' => $game->slug, 'per_page' => 5]));

        $response->assertSuccessful();

        $response->assertInertia(fn (Assert $page) => $page
            ->has('gamePlays.data', 5)
            ->where('gamePlays.total', 15)
            ->where('gamePlays.per_page', 5)
        );
    }
}