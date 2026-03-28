<?php

namespace NettSite\Messenger\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use NettSite\Messenger\Http\Requests\RegisterDeviceRequest;
use NettSite\Messenger\Models\MessengerUser;

class AuthController extends Controller
{
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        /** @var class-string<MessengerUser> $userModel */
        $userModel = config('messenger.user_model') ?? MessengerUser::class;

        if ($request->filled('user_id')) {
            $user = $userModel::findOrFail($request->user_id);
        } else {
            $user = MessengerUser::create([
                'name' => 'User',
                'email' => (string) Str::uuid().'@messenger.invalid',
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $user->registerDeviceToken($request->token, $request->platform);

        $user->tokens()->where('name', $request->token)->delete();
        $sanctumToken = $user->createToken($request->token)->plainTextToken;

        return response()->json([
            'user_id' => $user->getKey(),
            'token' => $sanctumToken,
        ]);
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
