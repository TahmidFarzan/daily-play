<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->is_super_admin) {
            return $next($request);
        }

        return to_route('auth-user.dashboard.index')->with('flash_message', [
            'message' => "warning",
            'status'  => __('You are not authorized to access.'),
        ]);
    }
}
