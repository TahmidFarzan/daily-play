<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Services\BackOffice\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameController extends Controller
{
    protected GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function index(Request $request): InertiaResponse
    {
        $game = $this->gameService->new();
        Gate::authorize('viewAny', $game);

        $games = $this->gameService->search($request);

        return Inertia::render('back-office/games/Index', [
            'games' => $games,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $game = $this->gameService->findBySlug($slug);
        Gate::authorize('view', $game);

        return Inertia::render('back-office/games/Details', [
            'game' => $game,
        ]);
    }
}
