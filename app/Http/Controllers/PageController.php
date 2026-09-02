<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Http\Requests\PlayerRequest;
use App\Http\Requests\GameScoreRequest;
use Illuminate\Http\JsonResponse;

use App\Services\GameService;
use App\Services\PlayerService;
use App\Services\GameScoreService;
use App\Services\GameChallengeService;

class PageController extends Controller
{
    protected GameService $gameService;
    protected PlayerService $playerService;
    protected GameScoreService $gameScoreService;
    protected GameChallengeService $gameChallengeService;


    public function __construct( PlayerService $playerService, GameService $gameService, GameChallengeService $gameChallengeService, GameScoreService $gameScoreService,)
    {
        $this->playerService = $playerService;
        $this->gameService = $gameService;
        $this->gameScoreService = $gameScoreService;
        $this->gameChallengeService = $gameChallengeService;
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
        $gameChallenge = $this->gameChallengeService->findByGameSlug(CacheHelper::KEY_PLAY_GAME_PAGE, $game);

        return Inertia::render('games/Play', [
            'gameChallenge' => $gameChallenge,
        ]);
    }


    public function gameScoreSave(GameScoreRequest $request, string $slug): JsonResponse
    {
        $game = $this->gameService->findBySlug(CacheHelper::KEY_GAME_DETAILS_PAGE, $slug);
        $gameChallenge = $this->gameChallengeService->findByGameSlug(CacheHelper::KEY_PLAY_GAME_PAGE, $game);

        $result = $this->gameScoreService->save($request, $gameChallenge);

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
