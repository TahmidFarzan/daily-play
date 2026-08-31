<?php
namespace App\Observers;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    public function creating(Activity $activity): void
    {
        if (! $activity->slug) {
            $mainSlug     = Str::uuid();
            $randomString = Str::random(11);
            $createdAt    = now()->format('HisdmY');

            $activity->slug = "{$createdAt}-{$randomString}-{$mainSlug}";
        }
    }
}
