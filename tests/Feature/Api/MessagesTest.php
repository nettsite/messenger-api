<?php

use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessageReceipt;
use NettSite\Messenger\Models\MessageRecipient;
use NettSite\Messenger\Models\MessengerUser;

it('returns paginated messages addressed to the authenticated user', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => now()]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'user',
        'recipient_id' => $user->getKey(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/messenger/messages')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns messages broadcast to all users', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => now()]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'all',
        'recipient_id' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/messenger/messages')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('does not return unsent messages', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => null]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'all',
        'recipient_id' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/messenger/messages')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('poll returns unseen messages and creates delivery receipts', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => now()]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'all',
        'recipient_id' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/messenger/messages/poll')
        ->assertOk()
        ->assertJsonCount(1);

    expect(
        MessageReceipt::where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->whereNotNull('delivered_at')
            ->exists()
    )->toBeTrue();
});

it('poll returns nothing on a second call once delivered', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => now()]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'all',
        'recipient_id' => null,
    ]);

    $this->actingAs($user, 'sanctum')->getJson('/api/messenger/messages/poll');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/messenger/messages/poll')
        ->assertOk()
        ->assertJsonCount(0);
});

it('marks a message as read', function () {
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create(['sent_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/read")
        ->assertOk();

    expect(
        MessageReceipt::where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->whereNotNull('read_at')
            ->exists()
    )->toBeTrue();
});

it('requires authentication for messages endpoints', function () {
    $this->getJson('/api/messenger/messages')->assertUnauthorized();
    $this->getJson('/api/messenger/messages/poll')->assertUnauthorized();
});
