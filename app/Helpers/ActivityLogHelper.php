<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class ActivityLogHelper
{
    private const ACTIVITY_LOG_CREATED  = 'Created';
    private const ACTIVITY_LOG_UPDATED  = 'Updated';
    private const ACTIVITY_LOG_DELETED  = 'Deleted';
    private const ACTIVITY_LOG_TRASHED  = 'Trashed';
    private const ACTIVITY_LOG_RESTORED = 'Restored';

    private const ACTIVITY_LOG_SUBJECT_USER            = 'User';
    private const ACTIVITY_LOG_SUBJECT_GAME_DIFFICULTY = 'Game Difficulty';

    public static function activityLogEvents(): Collection
    {
        return SystemHelper::toOptions([
            self::ACTIVITY_LOG_CREATED,
            self::ACTIVITY_LOG_UPDATED,
            self::ACTIVITY_LOG_DELETED,
            self::ACTIVITY_LOG_TRASHED,
            self::ACTIVITY_LOG_RESTORED,
        ]);
    }

    public static function activityLogSubjectTypes(): Collection
    {
        return SystemHelper::toOptions([
            self::ACTIVITY_LOG_SUBJECT_USER,
            self::ACTIVITY_LOG_SUBJECT_GAME_DIFFICULTY,
        ]);
    }
}
