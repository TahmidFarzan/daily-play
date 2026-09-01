<?php

namespace App\Services\GamePlay;

use App\Models\Game;
use App\Models\GameDifficulty;
use Carbon\CarbonInterface;

class ZipBoardGenerator
{
    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_NORMAL = 'normal';
    public const DIFFICULTY_HARD = 'hard';

    protected const SIZE_BY_DIFFICULTY = [
        self::DIFFICULTY_EASY => ['rows' => 5, 'cols' => 4, 'clues' => 6],
        self::DIFFICULTY_NORMAL => ['rows' => 6, 'cols' => 5, 'clues' => 7],
        self::DIFFICULTY_HARD => ['rows' => 7, 'cols' => 6, 'clues' => 8],
    ];

    protected const GENERATION_ATTEMPTS = 64;


    public function generate(Game $game, CarbonInterface $gameDate, ?GameDifficulty $gameDifficulty = null): array
    {
        $settings = $this->settingsFor($gameDifficulty);

        $path = $this->generateHamiltonianPath($settings['rows'], $settings['cols']);
        $clues = $this->placeClues($path, $settings['clues']);

        return [
            'rows' => $settings['rows'],
            'cols' => $settings['cols'],
            'clues' => $clues,
            'path' => $path,
        ];
    }

    protected function settingsFor(?GameDifficulty $gameDifficulty = null): array
    {
        if ($gameDifficulty) {
            $slug = $gameDifficulty->slug ?? $gameDifficulty->name;

            return self::SIZE_BY_DIFFICULTY[$slug] ?? self::SIZE_BY_DIFFICULTY[self::DIFFICULTY_NORMAL];
        }

        return self::SIZE_BY_DIFFICULTY[self::DIFFICULTY_NORMAL];
    }

    protected function generateHamiltonianPath(int $rows, int $cols): array
    {
        $totalCells = $rows * $cols;

        for ($attempt = 0; $attempt < self::GENERATION_ATTEMPTS; $attempt++) {
            $path = $this->randomWarnsdorffPath($rows, $cols, $totalCells);

            if (count($path) === $totalCells) {
                return $path;
            }
        }

        return $this->serpentinePath($rows, $cols);
    }

    protected function randomWarnsdorffPath(int $rows, int $cols, int $totalCells): array
    {
        $visited = array_fill(0, $rows, array_fill(0, $cols, false));

        $path = [];

        $dfs = function (int $row, int $col) use (&$dfs, &$visited, &$path, $rows, $cols, $totalCells): bool {
            $visited[$row][$col] = true;
            $path[] = ['row' => $row, 'col' => $col];

            if (count($path) === $totalCells) {
                return true;
            }

            $neighbors = $this->neighbors($row, $col, $rows, $cols);
            shuffle($neighbors);

            usort($neighbors, function (array $a, array $b) use ($visited): int {
                $degreeA = $this->unvisitedDegree($a['row'], $a['col'], $visited);
                $degreeB = $this->unvisitedDegree($b['row'], $b['col'], $visited);

                return $degreeA <=> $degreeB;
            });

            foreach ($neighbors as $neighbor) {
                if ($visited[$neighbor['row']][$neighbor['col']]) {
                    continue;
                }

                if ($dfs($neighbor['row'], $neighbor['col'])) {
                    return true;
                }
            }

            array_pop($path);
            $visited[$row][$col] = false;

            return false;
        };

        $startRow = random_int(0, $rows - 1);
        $startCol = random_int(0, $cols - 1);

        return $dfs($startRow, $startCol) ? $path : [];
    }

    protected function serpentinePath(int $rows, int $cols): array
    {
        $path = [];

        for ($row = 0; $row < $rows; $row++) {
            if ($row % 2 === 0) {
                for ($col = 0; $col < $cols; $col++) {
                    $path[] = ['row' => $row, 'col' => $col];
                }
            } else {
                for ($col = $cols - 1; $col >= 0; $col--) {
                    $path[] = ['row' => $row, 'col' => $col];
                }
            }
        }

        return $path;
    }

    protected function neighbors(int $row, int $col, int $rows, int $cols): array
    {
        $candidates = [
            ['row' => $row - 1, 'col' => $col],
            ['row' => $row + 1, 'col' => $col],
            ['row' => $row, 'col' => $col - 1],
            ['row' => $row, 'col' => $col + 1],
        ];

        return array_values(array_filter(
            $candidates,
            fn (array $cell): bool => $cell['row'] >= 0
                && $cell['row'] < $rows
                && $cell['col'] >= 0
                && $cell['col'] < $cols,
        ));
    }

    protected function unvisitedDegree(int $row, int $col, array $visited): int
    {
        return count(array_filter(
            $this->neighbors($row, $col, count($visited), count($visited[0])),
            fn (array $neighbor): bool => ! $visited[$neighbor['row']][$neighbor['col']],
        ));
    }

    protected function placeClues(array $path, int $totalClues): array
    {
        $totalClues = max(2, $totalClues);
        $cells = count($path);
        $step = ($cells - 1) / ($totalClues - 1);

        $clues = [];

        for ($index = 0; $index < $totalClues; $index++) {
            $position = (int) round($index * $step);

            $clues[] = [
                'row' => $path[$position]['row'],
                'col' => $path[$position]['col'],
                'number' => $index + 1,
            ];
        }

        return $clues;
    }
}
