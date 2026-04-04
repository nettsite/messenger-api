<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use NettSite\Messenger\Enums\RegistrationMode;
use NettSite\Messenger\Enums\UserStatus;
use NettSite\Messenger\Http\Requests\LoginRequest;
use NettSite\Messenger\Http\Requests\RegisterDeviceRequest;
use NettSite\Messenger\Http\Requests\RegisterUserRequest;
use NettSite\Messenger\Models\MessengerUser;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $mode = RegistrationMode::from(config('messenger.registration.mode', 'open'));

        if ($mode === RegistrationMode::Closed) {
            return response()->json(['message' => 'Registration is closed.'], 403);
        }

        $status = $mode === RegistrationMode::Approval
            ? UserStatus::Pending
            : UserStatus::Active;

        $user = MessengerUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $status,
        ]);

        if ($status === UserStatus::Pending) {
            return response()->json(['status' => 'pending'], 202);
        }

        $this->registerDevice($user, $request->fcm_token, $request->platform);
        $tokenName = $request->fcm_token ?? $request->platform;
        $user->tokens()->where('name', $tokenName)->delete();
        $sanctumToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'user_id' => $user->getKey(),
            'token' => $sanctumToken,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var class-string<MessengerUser> $userModel */
        $userModel = config('messenger.user_model') ?? MessengerUser::class;

        $user = $userModel::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->isPending()) {
            return response()->json([
                'message' => 'Your account is pending admin approval.',
                'status' => 'pending',
            ], 403);
        }

        if ($user->isSuspended()) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'status' => 'suspended',
            ], 403);
        }

        $this->registerDevice($user, $request->fcm_token, $request->platform);
        $tokenName = $request->fcm_token ?? $request->platform;
        $user->tokens()->where('name', $tokenName)->delete();
        $sanctumToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'user_id' => $user->getKey(),
            'token' => $sanctumToken,
        ]);
    }

    public function refreshDevice(RegisterDeviceRequest $request): JsonResponse
    {
        /** @var MessengerUser $user */
        $user = Auth::user();

        $user->registerDeviceToken($request->token, $request->platform);
        $user->tokens()->where('name', $request->token)->delete();
        $sanctumToken = $user->createToken($request->token)->plainTextToken;

        return response()->json(['token' => $sanctumToken]);
    }

    /** Register the FCM token if provided; no-op for web users with no token. */
    private function registerDevice(MessengerUser $user, ?string $fcmToken, string $platform): void
    {
        if ($fcmToken) {
            $user->registerDeviceToken($fcmToken, $platform);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            PersonalAccessToken::findToken($token)?->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }
}
