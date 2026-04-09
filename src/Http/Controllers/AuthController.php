<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Enums\RegistrationMode;
use NettSite\Messenger\Enums\UserStatus;
use NettSite\Messenger\Http\Requests\LoginRequest;
use NettSite\Messenger\Http\Requests\RegisterDeviceRequest;
use NettSite\Messenger\Http\Requests\RegisterUserRequest;
use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\MessengerEnrollment;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $mode = RegistrationMode::from(config('messenger.registration.mode', 'open'));

        if ($mode === RegistrationMode::Closed) {
            return response()->json(['message' => 'Registration is closed.'], 403);
        }

        /** @var class-string<Authenticatable> $userModel */
        $userModel = config('messenger.user_model');

        if ($userModel::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['email' => ['The email has already been taken.']],
            ], 422);
        }

        /** @var Authenticatable&MessengerAuthenticatable $user */
        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $enrollment = $this->enroll($user, $mode);

        if ($enrollment->status === UserStatus::Pending) {
            return response()->json(['status' => 'pending'], 202);
        }

        return $this->issueToken($user, $request->fcm_token, $request->platform);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var class-string<Authenticatable> $userModel */
        $userModel = config('messenger.user_model');

        /** @var (Authenticatable&MessengerAuthenticatable)|null $user */
        $user = $userModel::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $enrollment = $user->messengerEnrollment;

        if (! $enrollment) {
            // First time this host-app user has accessed messenger — apply the current rules.
            $mode = RegistrationMode::from(config('messenger.registration.mode', 'open'));

            if ($mode === RegistrationMode::Closed) {
                return response()->json(['message' => 'Registration is closed.'], 403);
            }

            $enrollment = $this->enroll($user, $mode);
        }

        if ($enrollment->status === UserStatus::Pending) {
            return response()->json(['message' => 'Your account is pending approval.', 'status' => 'pending'], 403);
        }

        if ($enrollment->status === UserStatus::Suspended) {
            return response()->json(['message' => 'Your account has been suspended.', 'status' => 'suspended'], 403);
        }

        return $this->issueToken($user, $request->fcm_token, $request->platform);
    }

    public function refreshDevice(RegisterDeviceRequest $request): JsonResponse
    {
        /** @var MessengerAuthenticatable $user */
        $user = Auth::user();

        $user->registerDeviceToken($request->token, $request->platform);
        $user->tokens()->where('name', $request->token)->delete();
        $sanctumToken = $user->createToken($request->token)->plainTextToken;

        return response()->json(['token' => $sanctumToken]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            $pat = PersonalAccessToken::findToken($token);

            if ($pat) {
                DeviceToken::where('token', $pat->name)->delete();
                $pat->delete();
            }
        }

        return response()->json(['message' => 'Logged out.']);
    }

    private function enroll(MessengerAuthenticatable $user, RegistrationMode $mode): MessengerEnrollment
    {
        $status = $mode === RegistrationMode::Approval ? UserStatus::Pending : UserStatus::Active;

        /** @var MessengerEnrollment $enrollment */
        $enrollment = $user->messengerEnrollment()->create([
            'status' => $status,
            'enrolled_at' => now(),
        ]);

        return $enrollment;
    }

    private function issueToken(MessengerAuthenticatable $user, ?string $fcmToken, string $platform): JsonResponse
    {
        if ($fcmToken) {
            $user->registerDeviceToken($fcmToken, $platform);
        }

        $tokenName = $fcmToken ?? $platform;
        $user->tokens()->where('name', $tokenName)->delete();
        $sanctumToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'user_id' => $user->getKey(),
            'token' => $sanctumToken,
        ]);
    }
}
