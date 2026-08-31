<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class UserPermissionHelper
{
    private const SEPARATOR = ': ';

    public const ACCESS_VIEW_ANY     = 'View any';
    public const ACCESS_VIEW         = 'View';
    public const ACCESS_CREATE       = 'Create';
    public const ACCESS_UPDATE       = 'Update';
    public const ACCESS_DELETE       = 'Delete';
    public const ACCESS_RESTORE      = 'Restore';
    public const ACCESS_FORCE_DELETE = 'Force delete';

    public const MODULE_USER            = 'User';
    public const MODULE_GAME_DIFFICULTY = 'Game Difficulty';

    public static function modules(): Collection
    {
        return SystemHelper::toOptions([
            self::MODULE_USER,
            self::MODULE_GAME_DIFFICULTY,
        ]);
    }

    public static function modulesPermissions(string $moduleName = self::MODULE_USER): Collection
    {
        $fullPermissionModules = [
            self::MODULE_USER,
        ];

        if (in_array($moduleName, $fullPermissionModules, true)) {
            return SystemHelper::toOptions([
                self::ACCESS_VIEW_ANY,
                self::ACCESS_VIEW,
                self::ACCESS_CREATE,
                self::ACCESS_UPDATE,
                self::ACCESS_DELETE,
                self::ACCESS_RESTORE,
                self::ACCESS_FORCE_DELETE,
            ]);
        }

        if ($moduleName == self::MODULE_GAME_DIFFICULTY) {
            return SystemHelper::toOptions([
                self::ACCESS_VIEW_ANY,
                self::ACCESS_VIEW,
            ]);
        }

        return SystemHelper::toOptions([
            self::ACCESS_VIEW_ANY,
            self::ACCESS_VIEW,
            self::ACCESS_CREATE,
            self::ACCESS_UPDATE,
            self::ACCESS_DELETE,
        ]);
    }

    public static function modulePermissingNameGenerates(string $moduleName, string $accessName): string
    {
        return $moduleName . self::SEPARATOR . $accessName;
    }
}
