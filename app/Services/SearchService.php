<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Helpers\QuizHelper;
use App\Helpers\DatatableHelper;
use App\Helpers\EventHelper;
use App\Helpers\GoogleAdHelper;
use App\Helpers\MenuHelper;
use App\Helpers\PageHelper;
use App\Helpers\UserHelper;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\MenuType;
use App\Models\Survey;
use App\Models\News;
use App\Models\NewsType;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SearchService
{
    public function perPages(Request $request): array
    {
        $options = DatatableHelper::perPages();

        if ($request->filled('search')) {
            $search  = strtolower($request->input('search'));
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function genders(Request $request): array
    {
        $options = UserHelper::genders();

        if ($request->filled('search')) {
            $search  = strtolower($request->input('search'));
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function religions(Request $request): array
    {
        $options = UserHelper::religions();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function maritalStatuses(Request $request): array
    {
        $options = UserHelper::maritalStatuses();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function activityLogEvents(Request $request): array
    {
        $options = ActivityLogHelper::activityLogEvents();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function activityLogSubjectTypes(Request $request): array
    {
        $options = ActivityLogHelper::activityLogSubjectTypes();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                    stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function users(Request $request): array
    {
        $query = User::query()
            ->whereNull('deleted_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'   => $user->id,
            'name' => $user->name,
            'slug' => $user->slug,

        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function userPermissions(Request $request): array
    {
        $query = UserPermission::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('module', 'like', "%{$search}%")
                    ->orWhere('access', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'     => $user->id,
            'module' => $user->module,
            'access' => $user->access,
            'name'   => $user->name,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function userPermissionsByGroup(Request $request): array
    {
        $query = UserPermission::query();

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('module', 'like', "%{$search}%")
                    ->orWhere('access', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereKeyNot($request->input('except_id'));
        }

        return $query
            ->orderBy('module')
            ->orderBy('access')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    public function medias(Request $request): array
    {
        $query = Media::query();

        if ($request->filled('search')) {
            $query->where(
                fn($q) =>
                $q->where('name', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('custom_properties->alt', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('custom_properties->caption', 'like', '%' . $request->input('search') . '%')
            );
        }

        if (
            $request->filled('media_type') &&
            Str::lower($request->input('media_type')) !== 'all'
        ) {
            $query->where('mime_type', 'like', $request->input('media_type') . '%');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 50));

        $list = $records->map(fn($media) => [
            'id'                => $media->id,
            'name'              => $media->name,
            'uuid'              => $media->uuid,
            'mime_type'         => $media->mime_type,
            'custom_properties' => $media->custom_properties,
            'caption'           => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
            'alt'               => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
            'media_type'        => $media->getTypeFromMime(),
            'original_url'      => $media->original_url,
            'preview_url'      => $media->preview_url,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function user(int | string $slugOrId): User
    {
        return User::with("userPermission")->where('id', $slugOrId)->orWhere('slug', $slugOrId)->firstOrFail();
    }

    public function userPermission(int | string $slugOrId): UserPermission
    {
        return UserPermission::where('id', $slugOrId)->firstOrFail();
    }
}
