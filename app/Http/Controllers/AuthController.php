<?php
namespace App\Http\Controllers;

use App\Http\Requests\AuthUserAccountRequest;
use App\Http\Requests\AuthUserProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->middleware('guest')->only([
            'login', 'register', 'forgotPassword', 'resetPassword',
            'loginForm', 'registerForm', 'forgetPasswordForm', 'resetPasswordForm',
        ]);

        $this->middleware('auth')->only([
            'logout', 'emailVerificationResend', 'dashboard',
            'profileIndex', 'accountIndex', 'profileUpdate', 'accountUpdate',
        ]);

        $this->middleware(['auth', 'signed'])->only(['emailVerification']);
        $this->middleware('throttle:3,1')->only(['login']);

        $this->authService      = $authService;
    }

    public function loginForm(): InertiaResponse
    {
        return Inertia::render('auth/Login');
    }

    public function registerForm(): never
    {
        abort(401, 'Registration is not allowed.');
    }

    public function forgetPasswordForm(): InertiaResponse
    {
        return Inertia::render('auth/ForgotPassword');
    }

    public function resetPasswordForm($token, $email): InertiaResponse
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function emailVerificationNotice(): InertiaResponse
    {
        return Inertia::render('auth/EmailVerification');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->login($request);

        if ($result['status'] === 'success') {
            $redirect = session()->pull('url.intended', route('auth-user.dashboard.index'));

            return redirect($redirect)->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function register(RegisterRequest $request): never
    {
        abort(401, 'Registration is not allowed.');
    }

    public function logout(): RedirectResponse
    {
        $result = $this->authService->logout();

        return to_route('login')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): RedirectResponse
    {
        $result = $this->authService->forgotPassword($request);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, string $token, string $email): RedirectResponse
    {
        $result = $this->authService->resetPassword($request, $token, $email);

        return to_route('login')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function emailVerification(Request $request, $id, $hash): RedirectResponse
    {
        $result = $this->authService->emailVerification($request, $id, $hash);

        $route = $result['status'] === 'success'
            ? 'auth-user.dashboard.index'
            : 'login';

        return to_route($route)->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function emailVerificationResend(Request $request): RedirectResponse
    {
        $result = $this->authService->emailVerificationResend($request);

        return back()->with('status', $result['message']);
    }

    public function profileIndex(): InertiaResponse
    {
        $user = $this->authService->authUser();

        return Inertia::render('auth-user/Profile', ['user' => $user]);
    }

    public function accountIndex(): InertiaResponse
    {
        $user = $this->authService->authUser();

        return Inertia::render('auth-user/Account', ['user' => $user]);
    }

    public function dashboard(): InertiaResponse
    {
        return Inertia::render('auth-user/Dashboard');
    }

    public function profileUpdate(AuthUserProfileRequest $request): RedirectResponse
    {
        $user   = $this->authService->authUser();
        $result = $this->authService->profileUpdate($request, $user);

        return to_route('auth-user.profile.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function accountUpdate(AuthUserAccountRequest $request): RedirectResponse
    {
        $user   = $this->authService->authUser();
        $result = $this->authService->accountUpdate($request, $user);

        return to_route('auth-user.account.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
