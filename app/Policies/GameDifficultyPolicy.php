<?php

namespace App\Policies;

use App\Helpers\UserPermissionHelper;
use App\Models\GameDifficulty;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GameDifficultyPolicy
{
    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->is_super_admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $authUser): Response
    {
        $module = UserPermissionHelper::MODULE_GAME_DIFFICULTY;
        $access = UserPermissionHelper::ACCESS_VIEW_ANY;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function view(User $authUser, GameDifficulty $gameDifficulty): Response
    {
        $module = UserPermissionHelper::MODULE_GAME_DIFFICULTY;
        $access = UserPermissionHelper::ACCESS_VIEW;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function create(User $authUser): Response
    {
        return Response::deny('Creating game difficulties is not allowed.');
    }

    public function delete(User $authUser, GameDifficulty $gameDifficulty): Response
    {
        return Response::deny('Deleting game difficulties is not allowed.');
    }
}
