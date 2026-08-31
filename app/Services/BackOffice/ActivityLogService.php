<?php
namespace App\Services\BackOffice;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    public function new(): Activity
    {
        return new Activity();
    }

    public function findBySlug(string $slug): Activity
    {
        return Activity::with([
            'causer',
            'subject',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function search(?string $modelSlug, ?string $recordSlug, Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $query = Activity::query();

        if ($modelSlug !== null && $recordSlug !== null) {
            $subjectModel = 'App\\Models\\' . Str::studly($modelSlug);

            $query->where('subject_type', $subjectModel)
                ->whereHas('subject', function ($subjectQuery) use ($recordSlug) {
                    $subjectQuery->where('slug', $recordSlug);
                });
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', 'like', '%' . $request->input('log_name') . '%');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function delete(Activity $activity): array
    {

        try {

            DB::transaction(function () use ($activity) {
                $activity->delete();
            });

            return [
                'status'  => 'success',
                'message' => 'Activity log deleted successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('Activity log delete failed.', [
                'exception'    => $exception->getMessage(),
                'activity_log' => $activity->toArray(),
                'trace'        => $exception->getTraceAsString(),
            ]);

            return [
                'status'  => 'error',
                'message' => 'An error occurred while deleting the activity log. Please check the logs for more details.',
            ];
        }
    }
}