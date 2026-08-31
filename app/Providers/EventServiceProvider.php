<?php
namespace App\Providers;

use App\Events\MediaUpdatedEvent;
use App\Listeners\MediaCreatedListener;
use App\Listeners\MediaUpdatedListener;
use App\Observers\ActivityObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(
            MediaHasBeenAddedEvent::class,
            [MediaCreatedListener::class, 'handle']
        );

        Event::listen(
            MediaUpdatedEvent::class,
            [MediaUpdatedListener::class, 'handle']
        );
        Activity::observe(ActivityObserver::class);
    }
}
