<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Http\Requests\MediaQuickRequest;
use App\Models\MediaUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    public function new(): Media
    {
        return new Media();
    }

    public function find(string $slug): array
    {
        $media = Media::with([
            'model',
        ])->where('slug', $slug)
            ->orWhere('uuid', $slug)
            ->firstOrFail();

        $media->activity_logs = $this->activityLogs($media);

        return $media->toArray();
    }

    public function firstById(string $id)
    {
        $media = Media::with([
            'model',
        ])->where('id', $id)->first();

        $media->activity_logs = $this->activityLogs($media);

        return $media->toArray();
    }

    public function activityLogs(Media $media)
    {
        return Activity::with('causer')->where('subject_type', get_class($media))->where('subject_id', $media->id)->latest()->get();
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Media::query()->with("model");

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'name',
                'email',
                'mobile',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function quickSave(MediaQuickRequest $request): array
    {
        try {
            $media = DB::transaction(function () use ($request) {
                $file = $request->file('media');

                if (! $file) {
                    return null;
                }

                $mediaUpload = MediaUpload::create();

                $extension = $file->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName('Upload', $extension, 200);

                return $mediaUpload->addMedia($file)
                    ->usingFileName($fileName)
                    ->withCustomProperties([
                        'caption' => $request->input('caption'),
                        'alt'     => $request->input('alt'),
                    ])
                    ->toMediaCollection($mediaUpload->media_collection_name);
            });

            if (! $media) {
                return [
                    'status'  => 'error',
                    'message' => 'Failed to save media. Please try again.',
                ];
            }

            $media->loadMissing('model');

            return [
                'status'  => 'success',
                'message' => 'Media saved successfully.',
                'media'   => (object) [
                    'id'                => $media?->id,
                    'name'              => $media?->name,
                    'uuid'              => $media?->uuid,
                    'slug'              => $media?->slug,
                    'mime_type'         => $media?->mime_type,
                    'custom_properties' => $media?->custom_properties,
                    'caption'           => $media?->getCustomProperty('caption') ?? $media?->model?->name ?? $media?->model?->title ?? '',
                    'alt'               => $media?->getCustomProperty('alt') ?? $media?->model?->name ?? $media?->model?->title ?? '',
                    'media_type'        => $media?->getTypeFromMime(),
                    'original_url'      => $media?->original_url,
                    'preview_url'       => $media?->preview_url,
                ],
            ];
        } catch (Exception $exception) {
            Log::error('Media save failed.', [
                'exception'    => $exception,
                'request_data' => $request->except(['media']),
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to save media. Please try again.',
            ];
        }
    }

    public function quickUpdate(MediaQuickRequest $request, $media)
    {
        try {

            DB::transaction(function () use ($request, $media) {
                $media->setCustomProperty(
                    'caption',
                    $request->input('caption', $media->getCustomProperty('caption'))
                );

                $media->setCustomProperty(
                    'alt',
                    $request->input('alt', $media->getCustomProperty('alt'))
                );
                $media->save();
            });

            return [
                'status'  => 'success',
                'message' => 'Media updated successfully.',
                'media'   => (object) [
                    'id'                => $media->id,
                    'name'              => $media->name,
                    'uuid'              => $media->uuid,
                    'slug'              => $media->slug,
                    'mime_type'         => $media->mime_type,
                    'custom_properties' => $media->custom_properties,
                    'caption'           => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
                    'alt'               => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
                    'media_type'        => $media->getTypeFromMime(),
                    'original_url'      => $media->original_url,
                    'preview_url'       => $media->preview_url,

                ],
            ];
        } catch (Exception $ex) {

            Log::error('Media update failed.', [
                'exception'    => $ex,
                'request_data' => $request->input(),
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to update media. Please try again.',
            ];
        }
    }

    public static function copyOrUpdateMediaByMediaIds(array $mediaIds, $targetModel, string $mediaRole = MediaHelper::ROLE_DEFAULT): array
    {
        $replacementPairs = [];

        foreach ($mediaIds as $mediaId) {
            $replacementPair = self::copyOrUpdateMediaByMediaId((int) $mediaId, $targetModel, $mediaRole);

            if ($replacementPair === null) {
                continue;
            }

            $replacementPairs[] = $replacementPair;
        }

        return $replacementPairs;
    }

    public static function copyOrUpdateMediaByMediaId(int $mediaId, object $targetModel, string $mediaRole = MediaHelper::ROLE_DEFAULT): ?object
    {
        try {
            return DB::transaction(function () use ($mediaId, $targetModel, $mediaRole) {
                $media = Media::query()
                    ->with('model')
                    ->lockForUpdate()
                    ->findOrFail($mediaId);

                $sourceModel = $media->model;

                if (! $sourceModel || ! $targetModel) {
                    return null;
                }

                $oldMediaId = $media->id;

                $targetName = $targetModel->name ?? $targetModel->title ?? $media->name;

                if ($sourceModel instanceof MediaUpload) {
                    $media->model_id        = $targetModel->id;
                    $media->model_type      = $targetModel->getMorphClass();
                    $media->name            = $targetName;
                    $media->collection_name = $targetModel->media_collection_name;

                    $media->setCustomProperty(
                        'caption',
                        $media->getCustomProperty('caption') ?? $targetName
                    );

                    $media->setCustomProperty(
                        'alt',
                        $media->getCustomProperty('alt') ?? $targetName
                    );

                    $media->setCustomProperty('role', $mediaRole);

                    $media->save();

                    if ($sourceModel->getMedia($sourceModel->media_collection_name)->isEmpty()) {
                        $sourceModel->delete();
                    }

                    return (object) [
                        'old_media_id' => $oldMediaId,
                        'new_media_id' => $media->id,
                    ];
                }

                $mediaExtension = pathinfo($media->original_url, PATHINFO_EXTENSION);

                $mediaFileName = MediaHelper::generateMediaName($targetName, $mediaExtension, 200);

                $newMedia = $targetModel
                    ->addMediaFromUrl($media->original_url)
                    ->usingName($targetName)
                    ->usingFileName($mediaFileName)
                    ->withCustomProperties([
                        'caption' => $media->getCustomProperty('caption') ?? $targetName,
                        'alt'     => $media->getCustomProperty('alt') ?? $targetName,
                        'role'    => $mediaRole,
                    ])
                    ->toMediaCollection($targetModel->media_collection_name);

                return (object) [
                    'old_media_id' => $oldMediaId,
                    'new_media_id' => $newMedia->id,
                ];
            });
        } catch (Exception $exception) {
            $targetModelName = $targetModel->name ?? $targetModel->title ?? '';

            Log::error("Failed to transfer media {$targetModelName}.", [
                'old_media_id' => $mediaId,
                'exception'    => $exception,
            ]);

            return null;
        }
    }

    public function delete($media): array
    {

        try {

            DB::transaction(function () use ($media) {
                // if (Storage::exists($media->getUrl())) {
                //     Storage::delete($media->getUrl());
                // }
                // if ($media->responsive_images) {
                //     foreach ($media->responsive_images as $responsiveImage) {
                //         if (Storage::exists($responsiveImage->getUrl())) {
                //             Storage::delete($responsiveImage->getUrl());
                //         }
                //     }
                // }

                $media->delete();
            });

            return [
                'status'  => 'success',
                'message' => 'Media deleted successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('Media delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to delete media. Please try again.',
            ];
        }
    }

}
