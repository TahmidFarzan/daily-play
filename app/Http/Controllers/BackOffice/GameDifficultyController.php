<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Services\BackOffice\GameDifficultyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameDifficultyController extends Controller
{
    protected GameDifficultyService $gameDifficultyService;

    public function __construct(GameDifficultyService $gameDifficultyService)
    {
        $this->gameDifficultyService = $gameDifficultyService;
    }

    public function index(Request $request): InertiaResponse
    {
        $gameDifficulty = $this->gameDifficultyService->new();
        Gate::authorize('viewAny', $gameDifficulty);

        $gameDifficulties = $this->gameDifficultyService->search($request);

        return Inertia::render('back-office/game-difficulties/Index', [
            'gameDifficulties' => $gameDifficulties,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $gameDifficulty = $this->gameDifficultyService->findBySlug($slug);
        Gate::authorize('view', $gameDifficulty);

        return Inertia::render('back-office/game-difficulties/Details', [
            'gameDifficulty' => $gameDifficulty,
        ]);
    }
}
