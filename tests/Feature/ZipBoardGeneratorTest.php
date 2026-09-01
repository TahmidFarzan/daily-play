<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameDifficulty;
use App\Models\User;
use App\Services\GamePlay\ZipBoardGenerator;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ZipBoardGeneratorTest extends TestCase
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

    protected function generator(): ZipBoardGenerator
    {
        return app(ZipBoardGenerator::class);
    }

    protected function createZipGame(): Game
    {
        return Game::factory()->state(['name' => 'Zip'])->create();
    }

    public function test_easy_normal_and_hard_shape_the_board(): void
    {
        $game = $this->createZipGame();
        $gameDate = Carbon::parse('2026-09-01 09:00:00');

        $easy = $this->generator()->generate($game, $gameDate, GameDifficulty::where('slug', 'easy')->firstOrFail());
        $normal = $this->generator()->generate($game, $gameDate, GameDifficulty::where('slug', 'normal')->firstOrFail());
        $hard = $this->generator()->generate($game, $gameDate, GameDifficulty::where('slug', 'hard')->firstOrFail());

        $expectedBySlug = [
            'easy' => ['rows' => 5, 'cols' => 4, 'clues' => 6, 'cells' => 20],
            'normal' => ['rows' => 6, 'cols' => 5, 'clues' => 7, 'cells' => 30],
            'hard' => ['rows' => 7, 'cols' => 6, 'clues' => 8, 'cells' => 42],
        ];

        foreach (['easy' => $easy, 'normal' => $normal, 'hard' => $hard] as $slug => $board) {
            $expected = $expectedBySlug[$slug];

            $this->assertSame($expected['rows'], $board['rows']);
            $this->assertSame($expected['cols'], $board['cols']);
            $this->assertCount($expected['clues'], $board['clues']);
            $this->assertCount($expected['cells'], $board['path']);
        }
    }

    public function test_generated_board_is_a_valid_hamiltonian_walk(): void
    {
        $game = $this->createZipGame();
        $difficulty = GameDifficulty::where('slug', 'normal')->firstOrFail();

        $board = $this->generator()->generate($game, now(), $difficulty);

        $rows = $board['rows'];
        $cols = $board['cols'];
        $path = $board['path'];

        $this->assertCount($rows * $cols, $path);

        $visited = [];
        foreach ($path as $cell) {
            $this->assertGreaterThanOrEqual(0, $cell['row']);
            $this->assertLessThan($rows, $cell['row']);
            $this->assertGreaterThanOrEqual(0, $cell['col']);
            $this->assertLessThan($cols, $cell['col']);

            $key = "{$cell['row']}:{$cell['col']}";
            $this->assertArrayNotHasKey($key, $visited, "Cell [{$key}] visited more than once.");
            $visited[$key] = true;
        }

        for ($index = 1; $index < count($path); $index++) {
            $distance = abs($path[$index]['row'] - $path[$index - 1]['row'])
                + abs($path[$index]['col'] - $path[$index - 1]['col']);

            $this->assertSame(1, $distance, 'Every consecutive pair must be orthogonally adjacent.');
        }
    }

    public function test_clues_are_numbered_in_order_and_anchor_the_path_ends(): void
    {
        $game = $this->createZipGame();
        $difficulty = GameDifficulty::where('slug', 'normal')->firstOrFail();

        $board = $this->generator()->generate($game, now(), $difficulty);

        $clueNumbers = array_column($board['clues'], 'number');
        $this->assertSame($clueNumbers, range(1, count($board['clues'])));

        $pathKeys = collect($board['path'])->map(fn (array $cell): string => "{$cell['row']}:{$cell['col']}")->all();

        foreach ($board['clues'] as $clue) {
            $this->assertContains("{$clue['row']}:{$clue['col']}", $pathKeys, 'Every clue must sit on the solution path.');
        }

        $firstClue = $board['clues'][0];
        $lastClue = $board['clues'][count($board['clues']) - 1];

        $this->assertSame($pathKeys[0], "{$firstClue['row']}:{$firstClue['col']}");
        $this->assertSame($pathKeys[count($pathKeys) - 1], "{$lastClue['row']}:{$lastClue['col']}");
    }

    public function test_generate_without_a_difficulty_falls_back_to_normal(): void
    {
        $game = $this->createZipGame();

        $board = $this->generator()->generate($game, now(), null);

        $this->assertSame(6, $board['rows']);
        $this->assertSame(5, $board['cols']);
        $this->assertCount(7, $board['clues']);
        $this->assertCount(30, $board['path']);
    }
}
