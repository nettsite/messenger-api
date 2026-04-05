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
use NettSite\Messenger\Http\Requests\LoginRequest;
use NettSite\Messenger\Http\Requests\RegisterDeviceRequest;
use NettSite\Messenger\Http\Requests\RegisterUserRequest;

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

        /** @var Authenticatable $user */
        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

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
        /** @var class-string<Authenticatable> $userModel */
        $userModel = config('messenger.user_model');

        $user = $userModel::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
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
        /** @var MessengerAuthenticatable $user */
        $user = Auth::user();

        $user->registerDeviceToken($request->token, $request->platform);
        $user->tokens()->where('name', $request->token)->delete();
        $sanctumToken = $user->createToken($request->token)->plainTextToken;

        return response()->json(['token' => $sanctumToken]);
    }

    /** Register the FCM token if provided; no-op for web users with no token. */
    private function registerDevice(MessengerAuthenticatable $user, ?string $fcmToken, string $platform): void
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
