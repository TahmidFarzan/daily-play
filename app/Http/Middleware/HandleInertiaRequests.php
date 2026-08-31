<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        $currentDate = now()->format('dmY');
        $version     = "v{$currentDate}";

        if (app()->environment('production')) {
            $version = parent::version($request);
        }
        if (! $version) {
            $version = "v{$currentDate}";
        }

        return $version;
    }

    public function share(Request $request): array
    {
        $requestUser = $request->user();

        if (Auth::check()) {
            $requestUser = Auth::user();
        }

        $requestData = array_merge(parent::share($request), [

            'auth'         => [
                'user' => $requestUser
                    ? [
                    'name'              => $requestUser->name,
                    'user_permissions'  => $requestUser->userPermissions,

                    'is_super_admin'    => $requestUser->is_super_admin,

                    'email'             => $requestUser->email,
                    'email_verified_at' => $requestUser->email_verified_at,
                    'slug'              => $requestUser->slug,
                    'profile_image'     => $requestUser->profile_image?->toArray() ?? null,
                ]
                    : null,
            ],

            'flashMessage' => $request->session()->get('flash_message') ?? null,
        ]);

        return $requestData;

        // return [
        //     ...parent::share($request),
        //     //
        // ];
    }
}
