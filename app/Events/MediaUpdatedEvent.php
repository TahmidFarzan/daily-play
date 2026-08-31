<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Media $media,
        public array $changes = []
    ) {}
}
