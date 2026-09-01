<?php

namespace App\Helpers;

class SeederHelper
{
    public const GAME_DIFFCULTIES = [
        [
            'name' => 'Easy',
            'brief' => 'A beginner-friendly difficulty level with more time and hints available.',
        ],
        [
            'name' => 'Normal',
            'brief' => 'The standard difficulty level for most players.',
        ],
        [
            'name' => 'Hard',
            'brief' => 'A challenging difficulty level for experienced players.',
        ],
    ];

    public const GAMES = [
        [
            'name' => 'Zip',
            'brief' => 'Connect the numbers in order to create a complete path while filling every cell in the grid.',
            'how_to_play' => 'Start at 1 and connect each number to the next in order. Create one continuous path and fill every cell in the grid exactly once. The path cannot cross itself. Complete the entire grid to solve the puzzle.',
            'logo_path' => 'uploads/images/logo/zip.png',
        ],
    ];
}
