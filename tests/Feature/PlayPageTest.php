<?php

namespace Tests\Feature;

use App\Models\GamePlay;
use App\Models\Game;
use App\Models\User;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlayPageTest extends TestCase
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

    public function test_play_route_returns_200_with_the_game_play_payload(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $response = $this->get(route('games.play', ['slug' => $game->slug]));

        $response->assertSuccessful();

        $gamePlay = GamePlay::where('game_id', $game->id)->whereDate('game_date', '2026-09-01')->firstOrFail();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('games/Play')
            ->missing('serverNow')
            ->missing('game')
            ->missing('board')
            ->missing('difficulty')
            ->missing('startsAt')
            ->missing('endsAt')
            ->has('gamePlay', fn (Assert $gamePlayAssert) => $gamePlayAssert
                ->where('id', $gamePlay->id)
                ->has('slug')
                ->where('game_date', '2026-09-01')
                ->has('starts_at')
                ->has('ends_at')
                ->has('board', fn (Assert $boardAssert) => $boardAssert
                    ->where('rows', $gamePlay->board['rows'])
                    ->where('cols', $gamePlay->board['cols'])
                    ->has('clues')
                    ->where('walls', $gamePlay->board['walls'])
                )
                ->has('game', fn (Assert $gameAssert) => $gameAssert
                    ->where('id', $gamePlay->game->id)
                    ->where('slug', $gamePlay->game->slug)
                    ->where('name', $gamePlay->game->name)
                    ->has('brief')
                    ->has('how_to_play')
                    ->has('logo')
                )
                ->has('gameDifficulty', fn (Assert $difficultyAssert) => $difficultyAssert
                    ->where('id', $gamePlay->game_difficulty_id)
                    ->where('slug', $gamePlay->gameDifficulty->slug)
                    ->where('name', $gamePlay->gameDifficulty->name)
                )
            )
        );
    }

    public function test_play_route_returns_the_same_game_play_on_refresh(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $first = $this->get(route('games.play', ['slug' => $game->slug]));
        $first->assertSuccessful();

        $second = $this->get(route('games.play', ['slug' => $game->slug]));
        $second->assertSuccessful();

        $firstGamePlay = $first->viewData('page')['props']['gamePlay'];
        $secondGamePlay = $second->viewData('page')['props']['gamePlay'];

        $this->assertSame($firstGamePlay['id'], $secondGamePlay['id']);
        $this->assertSame($firstGamePlay['board'], $secondGamePlay['board']);
        $this->assertSame($firstGamePlay['gameDifficulty']['id'], $secondGamePlay['gameDifficulty']['id']);
        $this->assertSame(1, GamePlay::count());
    }

    public function test_play_route_does_not_expose_the_solution_path(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $response = $this->get(route('games.play', ['slug' => $game->slug]));

        $response->assertSuccessful();

        $board = $response->viewData('page')['props']['gamePlay']['board'];

        $this->assertArrayHasKey('rows', $board);
        $this->assertArrayHasKey('cols', $board);
        $this->assertArrayHasKey('clues', $board);
        $this->assertArrayHasKey('walls', $board);
        $this->assertArrayNotHasKey('path', $board);
    }

    public function test_play_route_creates_game_play_with_a_slug(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $game = $this->createZipGame();

        $response = $this->get(route('games.play', ['slug' => $game->slug]));

        $response->assertSuccessful();

        $gamePlay = GamePlay::where('game_id', $game->id)->whereDate('game_date', '2026-09-01')->firstOrFail();

        $this->assertNotEmpty($gamePlay->slug);
    }

    public function test_play_route_for_an_unknown_game_returns_404(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 09:00:00'));

        $response = $this->get(route('games.play', ['slug' => 'not-a-game']));

        $response->assertNotFound();
        $this->assertSame(0, GamePlay::count());
    }
}
