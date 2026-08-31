<?php

namespace App\Services\BackOffice;

use App\Models\GameDifficulty;
use Illuminate\Http\Request;

class GameDifficultyService
{
    public function new(): GameDifficulty
    {
        return new GameDifficulty;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = GameDifficulty::query();

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'name',
                'brief',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function findBySlug(string $slug): GameDifficulty
    {
        return GameDifficulty::with([
            'createdBy',

            'activityLogs' => fn ($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }
}
