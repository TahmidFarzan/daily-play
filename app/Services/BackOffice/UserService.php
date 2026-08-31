<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function new(): User
    {
        return new User();
    }

    public function findBySlug(string $slug): User
    {
        return User::with([
            'userPermissions',
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function findTrashedBySlug(string $slug): User
    {
        return User::with([
            'userPermissions',
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->onlyTrashed()->where('slug', $slug)->firstOrFail();
    }

    public function findWithTrashedBySlug(string $slug): User
    {
        return User::with([
            'userPermissions',
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->withTrashed()->where('slug', $slug)->firstOrFail();
    }

    public function search(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $perPage = $request->input('per_page', 10);

        $query = User::withTrashed();

        if ($request->filled('is_active') && $request->boolean('is_active') == true) {
            $query->whereNull('deleted_at');
        }

        if ($request->filled('is_active') && $request->boolean('is_active') == false) {
            $query->whereNotNull('deleted_at');
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('user_permission_id')) {
            $query->whereHas(
                'userPermissions',
                function ($userPermissionQuery) use ($request) {
                    $userPermissionQuery->where(
                        'user_permissions.id',
                        $request->input('user_permission_id')
                    );
                }
            );
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
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

    public function save(UserRequest $request, User $user): array
    {
        $isNew = empty($user->id);

        try {

            $user = DB::transaction(function () use ($request, $user, $isNew) {

                $user->name           = $request->input('name');
                $user->email          = $request->input('email');
                $user->gender         = $request->input('gender');
                $user->mobile         = $request->input('mobile');
                $user->address        = $request->input('address');
                $user->religion       = $request->input('religion');
                $user->birth_date     = $request->input('birth_date');
                $user->marital_status = $request->input('marital_status');

                if (Auth::user()->is_super_admin) {
                    $user->is_super_admin = $request->boolean('is_super_admin') ? true : false;
                }

                $user->is_default = false;

                $user->password          = Hash::make($request->password);
                $user->created_by_id     = $isNew ? Auth::id() : $user->created_by_id;
                $user->email_verified_at = $request->boolean('set_as_verify_email') ? now() : $user->email_verified_at;

                $user->save();

                if ($user->is_super_admin == false) {
                    self::syncUserPermission($request, $user);
                }
                self::saveProfileImage($request, $user);

                return $user;
            });

            if (! $user->hasVerifiedEmail() || $request->boolean('send_verify_email')) {
                $user->sendEmailVerificationNotification();
            }

            return [
                'status'  => 'success',
                'message' => $isNew ? 'User created successfully.' : 'User updated successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('User save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to save user. Please try again.',
            ];
        }
    }

    public function activate(User $user): array
    {
        try {
            DB::transaction(function () use ($user) {
                $user->restore();
            });

            return [
                'status'  => 'success',
                'message' => 'User activated successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('User activate failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to activate user. Please try again.',
            ];
        }
    }

    public function inactive(User $user): array
    {
        try {
            DB::transaction(function () use ($user) {
                $user->delete();
            });

            return [
                'status'  => 'success',
                'message' => 'User deactivated successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('User deactivate failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to deactivate user. Please try again.',
            ];
        }
    }

    public function delete(User $user): array
    {

        try {

            DB::transaction(function () use ($user) {
                $user->forceDelete();
            });

            return [
                'status'  => 'success',
                'message' => 'User deleted permanently successfully.',
            ];
        } catch (Exception $exception) {

            Log::error('User delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Failed to delete user. Please try again.',
            ];
        }
    }

    private static function saveProfileImage(UserRequest $request, User $user): void
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        if ($user->profileImage) {
            $user->profileImage?->delete();
        }

        $uploaded = $request->file('profile_image');

        if ($uploaded) {
            $name = MediaHelper::generateMediaName(
                $user->name,
                $uploaded->getClientOriginalExtension(),
                200
            );

            $user->addMedia($uploaded)
                ->usingFileName($name)
                ->withCustomProperties([
                    'alt'     => $user->name ?? null,
                    'caption' => $user->name ?? null,
                    'role'    => MediaHelper::ROLE_PROFILE_IMAGE,
                ])
                ->toMediaCollection($user->media_collection_name);
        }
    }

    private static function syncUserPermission(UserRequest $request, User $user): void
    {
        if ($request->has('user_permission_ids')) {
            $user->userPermissions()->sync(
                (array) $request->input(
                    'user_permission_ids',
                    []
                )
            );
        }
    }
}
