<?php

use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Tests\Models\User;

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------

it('registers a new user in open mode and returns a sanctum token', function () {
    config()->set('messenger.registration.mode', 'open');

    $response = $this->postJson('/api/messenger/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'fcm_token' => 'fcm-device-token',
        'platform' => 'android',
    ]);

    $response->assertOk()->assertJsonStructure(['user_id', 'token']);

    expect(DeviceToken::where('token', 'fcm-device-token')->exists())->toBeTrue();
    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('returns 403 when registration mode is closed', function () {
    config()->set('messenger.registration.mode', 'closed');

    $this->postJson('/api/messenger/auth/register', [
        'name' => 'Blocked Bob',
        'email' => 'bob@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'fcm_token' => 'fcm-device-token',
        'platform' => 'android',
    ])->assertForbidden();
});

it('returns 422 when email is already taken', function () {
    config()->set('messenger.registration.mode', 'open');
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/messenger/auth/register', [
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'fcm_token' => 'fcm-device-token',
        'platform' => 'android',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('returns 422 when password confirmation does not match', function () {
    config()->set('messenger.registration.mode', 'open');

    $this->postJson('/api/messenger/auth/register', [
        'name' => 'Mismatch',
        'email' => 'mismatch@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'different',
        'fcm_token' => 'fcm-device-token',
        'platform' => 'android',
    ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

it('returns 422 when platform is invalid', function () {
    config()->set('messenger.registration.mode', 'open');

    $this->postJson('/api/messenger/auth/register', [
        'name' => 'Bad Platform',
        'email' => 'platform@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'fcm_token' => 'fcm-device-token',
        'platform' => 'windows',
    ])->assertUnprocessable()->assertJsonValidationErrors(['platform']);
});

it('registers a web user without an fcm token', function () {
    config()->set('messenger.registration.mode', 'open');

    $response = $this->postJson('/api/messenger/auth/register', [
        'name' => 'Web User',
        'email' => 'webuser@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'platform' => 'web',
    ]);

    $response->assertOk()->assertJsonStructure(['user_id', 'token']);
});

it('logs in a web user without an fcm token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    $this->postJson('/api/messenger/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'platform' => 'web',
    ])->assertOk()->assertJsonStructure(['user_id', 'token']);
});

// ---------------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------------

it('logs in a user and returns a sanctum token and device token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    $response = $this->postJson('/api/messenger/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'fcm_token' => 'fcm-login-token',
        'platform' => 'android',
    ]);

    $response->assertOk()->assertJsonStructure(['user_id', 'token']);
    expect($response->json('user_id'))->toBe($user->getKey());
    expect(DeviceToken::where('token', 'fcm-login-token')->exists())->toBeTrue();
});

it('returns 401 for invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);

    $this->postJson('/api/messenger/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
        'fcm_token' => 'fcm-token',
        'platform' => 'android',
    ])->assertUnauthorized();
});

it('returns 401 for unknown email', function () {
    $this->postJson('/api/messenger/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'secret1234',
        'fcm_token' => 'fcm-token',
        'platform' => 'android',
    ])->assertUnauthorized();
});

// ---------------------------------------------------------------------------
// FCM device token rotation
// ---------------------------------------------------------------------------

it('refreshes the device token for an authenticated user', function () {
    $user = User::factory()->create();
    $tokenResult = $user->createToken('old-fcm-token');

    $response = $this->withToken($tokenResult->plainTextToken)
        ->postJson('/api/messenger/auth/device', [
            'token' => 'new-fcm-token',
            'platform' => 'android',
        ]);

    $response->assertOk()->assertJsonStructure(['token']);
    expect(DeviceToken::where('token', 'new-fcm-token')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Logout
// ---------------------------------------------------------------------------

it('logs out and revokes the sanctum token', function () {
    $user = User::factory()->create();
    $tokenResult = $user->createToken('device');

    $this->withToken($tokenResult->plainTextToken)
        ->deleteJson('/api/messenger/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Rate limiting
// ---------------------------------------------------------------------------

it('throttles the register endpoint after 5 attempts per minute', function () {
    config()->set('messenger.registration.mode', 'open');

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/messenger/auth/register', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'fcm_token' => "token-{$i}",
            'platform' => 'android',
        ]);
    }

    $this->postJson('/api/messenger/auth/register', [
        'name' => 'Sixth',
        'email' => 'sixth@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'fcm_token' => 'token-6',
        'platform' => 'android',
    ])->assertTooManyRequests();
});
