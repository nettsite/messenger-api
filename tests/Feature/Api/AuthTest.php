<?php

use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\MessengerUser;

it('registers a new user and device token, returning a sanctum token', function () {
    $response = $this->postJson('/api/messenger/auth/register', [
        'token' => 'fcm-device-token',
        'platform' => 'android',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user_id', 'token']);

    expect(DeviceToken::where('token', 'fcm-device-token')->exists())->toBeTrue();
});

it('registers an existing user by user_id', function () {
    $user = MessengerUser::factory()->create();

    $response = $this->postJson('/api/messenger/auth/register', [
        'token' => 'fcm-device-token',
        'platform' => 'ios',
        'user_id' => $user->getKey(),
    ]);

    $response->assertOk();
    expect($response->json('user_id'))->toBe($user->getKey());
    expect(DeviceToken::where('user_id', $user->getKey())->exists())->toBeTrue();
});

it('rejects registration with an invalid platform', function () {
    $this->postJson('/api/messenger/auth/register', [
        'token' => 'fcm-device-token',
        'platform' => 'windows',
    ])->assertUnprocessable();
});

it('logs out and revokes the sanctum token', function () {
    $user = MessengerUser::factory()->create();
    $tokenResult = $user->createToken('device');

    $this->withToken($tokenResult->plainTextToken)
        ->deleteJson('/api/messenger/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
