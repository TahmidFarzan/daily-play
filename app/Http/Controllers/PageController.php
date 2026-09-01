<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Models\DailyGame;
use App\Services\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function home(Request $request): InertiaResponse
    {
        $games = $this->pageService->games($request, CacheHelper::KEY_HOME_PAGE);

        return Inertia::render('Home', [
            'games' => $games,
        ]);
    }

    public function gamePlay(string $slug): InertiaResponse
    {
        $dailyGame = $this->pageService->dailyGameByGameSlug(CacheHelper::KEY_PLAY_GAME_PAGE, $slug);

        return Inertia::render('games/Play', [
            'dailyGame' => $dailyGame,
        ]);
    }

    public function gameDetails(string $slug): InertiaResponse
    {
        $game = $this->pageService->gameBySlug(CacheHelper::KEY_GAME_DETAILS_PAGE, $slug);

        return Inertia::render('games/Details', [
            'game' => $game,
        ]);
    }
}
