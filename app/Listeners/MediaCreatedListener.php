<?php
namespace App\Listeners;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class MediaCreatedListener
{
    public function __construct()
    {
        //
    }

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        $media->created_by_id = Auth::user()?->id ?? null;

        if (! $media->slug) {
            $mainSlug     = Str::uuid();
            $randomString = Str::random(11);
            $createdAt    =  now()->format('HisdmY');

            $media->slug  = "{$createdAt}-{$randomString}-{$mainSlug}";
        }

        $media->save();

        activity('Media')
            ->performedOn($media)
            ->event('created')
            ->causedBy(Auth::user() ?? null)
            ->withProperties([
                'id'                    => $media->id,
                'uuid'                  => $media->uuid,
                'name'                  => $media->name,
                'file_name'             => $media->file_name,
                'collection_name'       => $media->collection_name,
                'disk'                  => $media->disk,
                'conversions_disk'      => $media->conversions_disk,
                'mime_type'             => $media->mime_type,
                'size'                  => $media->size,
                'model_type'            => $media->model_type,
                'model_id'              => $media->model_id,
                'custom_properties'     => $media->custom_properties,
                'generated_conversions' => $media->generated_conversions,
                'responsive_images'     => $media->responsive_images,
                'order_column'          => $media->order_column,
                'created_by_id'         => $media->created_by_id,
                'slug'                  => $media->slug,
                'url'                   => $media->getUrl(),
            ])
            ->log("The record has been created.");
    }
}
