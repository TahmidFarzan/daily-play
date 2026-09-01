<?php

namespace App\Policies;

use App\Helpers\UserPermissionHelper;
use App\Models\Game;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GamePolicy
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
        $module = UserPermissionHelper::MODULE_GAME;
        $access = UserPermissionHelper::ACCESS_VIEW_ANY;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function view(User $authUser, Game $game): Response
    {
        $module = UserPermissionHelper::MODULE_GAME;
        $access = UserPermissionHelper::ACCESS_VIEW;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function create(User $authUser): Response
    {
        return Response::deny('Creating game is not allowed.');
    }

    public function delete(User $authUser, Game $game): Response
    {
        return Response::deny('Deleting game is not allowed.');
    }
}
