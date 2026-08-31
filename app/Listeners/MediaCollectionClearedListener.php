<?php
namespace App\Listeners;

use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenClearedEvent;

class MediaCollectionClearedListener
{

    public function __construct()
    {
        //
    }

    public function handle(CollectionHasBeenClearedEvent $event): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $event->model;

        activity('media')
            ->performedOn($model)
            ->causedBy(Auth::user() ?? null)
            ->withProperties([
                'collection' => $event->collectionName,
                'model_type' => $event->model::class,
                'model_id'   => $event->model->id,
            ])
            ->log("Media collection cleared: {$event->collectionName}");
    }
}
