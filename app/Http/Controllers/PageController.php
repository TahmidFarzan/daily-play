<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Http\Requests\PlayerRequest;
use App\Http\Requests\GamePlayResultRequest;
use Illuminate\Http\JsonResponse;

use App\Services\GameService;
use App\Services\PlayerService;
use App\Services\GamePlayResultService;
use App\Services\GamePlayService;

class PageController extends Controller
{
    protected GameService $gameService;
    protected PlayerService $playerService;
    protected GamePlayResultService $gamePlayResultService;
    protected GamePlayService $gamePlayService;


    public function __construct( PlayerService $playerService, GameService $gameService, GamePlayService $gamePlayService, GamePlayResultService $gamePlayResultService,)
    {
        $this->playerService = $playerService;
        $this->gameService = $gameService;
        $this->gamePlayResultService = $gamePlayResultService;
        $this->gamePlayService = $gamePlayService;
    }

    public function home(Request $request): InertiaResponse
    {
        $games = $this->gameService->search($request, CacheHelper::KEY_HOME_PAGE);

        return Inertia::render('Home', [
            'games' => $games,
        ]);
    }

    public function gameDetails(string $slug): InertiaResponse
    {
        $game = $this->gameService->findBySlug(CacheHelper::KEY_GAME_DETAILS_PAGE, $slug);

        return Inertia::render('games/Details', [
            'game' => $game,
        ]);
    }

    public function gamePlay(string $slug): InertiaResponse
    {
        $game = $this->gameService->findBySlug(CacheHelper::KEY_PLAY_GAME_PAGE, $slug);
        $gamePlay = $this->gamePlayService->findByGameSlug(CacheHelper::KEY_PLAY_GAME_PAGE, $game);

        return Inertia::render('games/Play', [
            'gamePlay' => $gamePlay,
        ]);
    }


    public function gamePlayResultSave(GamePlayResultRequest $request, string $slug): JsonResponse
    {
        $game = $this->gameService->findBySlug(CacheHelper::KEY_GAME_DETAILS_PAGE, $slug);
        $gamePlay = $this->gamePlayService->findByGameSlug(CacheHelper::KEY_PLAY_GAME_PAGE, $game);

        $result = $this->gamePlayResultService->save($request, $gamePlay);

        return response()->json(
            $result,
            $result['status'] === 'success' ? 200 : 500
        );
    }


    public function playerGet(string $slug): JsonResponse
    {
        $result = $this->playerService->firstBySlug($slug);

        return response()->json(
            $result,
            $result['status'] === 'success' ? 200 : 404
        );
    }

    public function playerSave(PlayerRequest $request): JsonResponse
    {
        $result = $this->playerService->save($request);

        return response()->json(
            $result,
            $result['status'] === 'success' ? 200 : 500
        );
    }
}
