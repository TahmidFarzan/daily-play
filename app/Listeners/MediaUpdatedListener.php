<?php
namespace App\Listeners;

namespace App\Listeners;

use App\Events\MediaUpdatedEvent;
use Illuminate\Support\Facades\Auth;

class MediaUpdatedListener
{
    public function handle(MediaUpdatedEvent $event): void
    {
        $media = $event->media;

        activity('Media')
            ->performedOn($media)
            ->causedBy(Auth::user())
            ->withProperties([
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
            ->log('Media updated');
    }
}
