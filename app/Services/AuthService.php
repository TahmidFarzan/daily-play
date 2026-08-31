<?php
namespace App\Services;

use App\Helpers\MediaHelper;
use App\Http\Requests\AuthUserAccountRequest;
use App\Http\Requests\AuthUserProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified as VerifiedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function authUser(): User
    {
        return $this->findBySlug(Auth::user()->slug);
    }

    public function findBySlug(int | string $slugOrId): User
    {
        return User::with([
            'media',
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function login(LoginRequest $request): array
    {
        try {
            $credentials = $request->only('email', 'password');

            if (! Auth::attempt($credentials, $request->boolean('remember'))) {
                return ['status' => 'error', 'message' => __('status-messages.auth.login.credential_fail')];
            }

            session()->regenerate();

            return ['status' => 'success', 'message' => __('status-messages.auth.login.success')];
        } catch (Exception $exception) {
            Log::error('Login error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.login.fail')];
        }
    }

    public function register(RegisterRequest $request): array
    {
        try {
            $user = User::create([
                'name'           => $request->name,
                'email'          => $request->email,
                'password'       => Hash::make($request->password),
                'created_by_id'  => null,

                'is_super_admin' => false,
                'is_default'     => false,
                'created_at'     => now(),
                'updated_at'     => null,
            ]);

            event(new Registered($user));
            Auth::login($user);

            return ['status' => 'success', 'message' => __('status-messages.auth.register.success')];
        } catch (Exception $exception) {
            Log::error('Register error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.register.fail')];
        }
    }

    public function logout(): array
    {
        try {
            if (Auth::check()) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
            }

            return ['status' => 'success', 'message' => __('status-messages.auth.logout.success')];
        } catch (Exception $exception) {
            Log::error('Logout error.', ['exception' => $exception, 'request_data' => Auth::user()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.logout.fail')];
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): array
    {
        try {
            $status = Password::sendResetLink($request->only('email'));

            return $status === Password::RESET_LINK_SENT
                ? ['status' => 'success', 'message' => __('status-messages.auth.forget_password.success')]
                : ['status' => 'error', 'message' => __('status-messages.auth.forget_password.fail')];
        } catch (Exception $exception) {
            Log::error('Forget password error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.forget_password.fail')];
        }
    }

    public function resetPassword(ResetPasswordRequest $request, string $token, string $email): array
    {
        try {
            $status = Password::reset(
                [
                    'email'                 => $request->email,
                    'password'              => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token'                 => $request->token,
                ],
                function ($user) use ($request) {
                    $user->forceFill(['password' => Hash::make($request->password)])->save();
                    event(new PasswordReset($user));
                }
            );

            return $status === Password::PASSWORD_RESET
                ? ['status' => 'success', 'message' => __('status-messages.auth.reset_password.success')]
                : ['status' => 'error', 'message' => __('status-messages.auth.reset_password.fail')];
        } catch (Exception $exception) {
            Log::error('Reset password error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.reset_password.fail')];
        }
    }

    public function emailVerification(Request $request, $id, $hash): array
    {
        try {
            $user = $request->user();

            if ($user->markEmailAsVerified()) {
                return ['status' => 'success', 'message' => __('status-messages.auth.email_verification.success')];
            }

            if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return ['status' => 'error', 'message' => __('status-messages.auth.email_verification.expired')];
            }

            event(new VerifiedEvent($user));

            return ['status' => 'error', 'message' => __('status-messages.auth.email_verification.fail')];
        } catch (Exception $exception) {
            Log::error('Email verify error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.email_verification.fail')];
        }
    }

    public function emailVerificationResend(Request $request): array
    {
        try {
            $request->user()->sendEmailVerificationNotification();
            return ['status' => 'success', 'message' => __('status-messages.auth.email_verification.request_success')];
        } catch (Exception $exception) {
            Log::error('Email verify resend error.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.email_verification.request_fail')];
        }
    }

    public function profileUpdate(AuthUserProfileRequest $request, User $user): array
    {
        try {

            DB::transaction(function () use ($request, $user) {
                $user->name           = $request->input('name');
                $user->birth_date     = $request->input('birth_date');
                $user->gender         = $request->input('gender');
                $user->religion       = $request->input('religion');
                $user->marital_status = $request->input('marital_status');
                $user->mobile         = $request->input('mobile');
                $user->address        = $request->input('address');
                $user->save();

                self::saveUserProfileImage($request, $user);
            });

            return ['status' => 'success', 'message' => __('status-messages.auth.profile.save.success')];
        } catch (Exception $exception) {

            Log::error('Auth profile update fail.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.profile.save.fail')];
        }
    }

    public function accountUpdate(AuthUserAccountRequest $request, User $user): array
    {
        try {

            $requiresVerification = $request->input('email') !== Auth::user()->email;

            $user = DB::transaction(function () use ($request, $requiresVerification, $user) {

                $user->name              = $request->input('name');
                $user->email             = $request->input('email');
                $user->email_verified_at = $requiresVerification ? null : $user->email_verified_at;

                if ($request->change_password == 1) {
                    $user->password = Hash::make($request->input('password'));
                }

                $user->save();

                return $user;
            });

            if ($requiresVerification) {
                $user->sendEmailVerificationNotification();
            }

            return ['status' => 'success', 'message' => __('status-messages.auth.account.save.success')];
        } catch (Exception $exception) {

            Log::error('Auth account update fail.', ['exception' => $exception, 'request_data' => $request->input()]);
            return ['status' => 'error', 'message' => __('status-messages.auth.account.save.fail')];
        }
    }

    private static function saveUserProfileImage(AuthUserProfileRequest $request, User $user)
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        $existing = $user->profileImage();
        if ($existing) {
            $existing->delete();
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
}
