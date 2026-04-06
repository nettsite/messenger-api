<?php

use Illuminate\Support\Facades\Queue;
use NettSite\Messenger\Jobs\SendConversationMessageJob;
use NettSite\Messenger\Models\Conversation;
use NettSite\Messenger\Models\ConversationMessage;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Tests\Models\User;

it('returns 404 when no conversation exists yet', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/messenger/messages/{$message->getKey()}/conversation")
        ->assertNotFound();
});

it('creates a conversation and appends a message', function () {
    Queue::fake();

    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => 'Hello, I have a question.',
        ])
        ->assertCreated();

    expect(
        Conversation::where('message_id', $message->getKey())
            ->where('user_type', User::class)
            ->where('user_id', $user->getKey())
            ->exists()
    )->toBeTrue();

    expect(
        ConversationMessage::whereHas('conversation', fn ($q) => $q->where('message_id', $message->getKey()))
            ->where('author_id', $user->getKey())
            ->where('body', 'Hello, I have a question.')
            ->exists()
    )->toBeTrue();
});

it('reuses the existing conversation on a second message', function () {
    Queue::fake();

    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => 'First message.',
        ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => 'Second message.',
        ])
        ->assertCreated();

    expect(
        Conversation::where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->count()
    )->toBe(1);

    expect(
        ConversationMessage::whereHas('conversation', fn ($q) => $q->where('message_id', $message->getKey()))
            ->count()
    )->toBe(2);
});

it('returns the conversation with messages after posting', function () {
    Queue::fake();

    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => 'Can you help me?',
        ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/messenger/messages/{$message->getKey()}/conversation")
        ->assertOk()
        ->assertJsonPath('message_id', $message->getKey())
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.body', 'Can you help me?');
});

it('marks unread admin-authored messages as read on GET', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $message = Message::factory()->create();

    $conversation = Conversation::create([
        'message_id' => $message->getKey(),
        'user_type' => User::class,
        'user_id' => $user->getKey(),
    ]);

    $adminMessage = $conversation->messages()->create([
        'author_type' => User::class,
        'author_id' => $admin->getKey(),
        'body' => 'Admin reply here.',
        'read_at' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/messenger/messages/{$message->getKey()}/conversation")
        ->assertOk();

    expect($adminMessage->fresh()->read_at)->not->toBeNull();
});

it('does not mark user-authored messages as read on GET', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $conversation = Conversation::create([
        'message_id' => $message->getKey(),
        'user_type' => User::class,
        'user_id' => $user->getKey(),
    ]);

    $userMessage = $conversation->messages()->create([
        'author_type' => User::class,
        'author_id' => $user->getKey(),
        'body' => 'User message.',
        'read_at' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/messenger/messages/{$message->getKey()}/conversation")
        ->assertOk();

    expect($userMessage->fresh()->read_at)->toBeNull();
});

it('dispatches the job when a message is sent', function () {
    Queue::fake();

    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => 'Testing dispatch.',
        ])
        ->assertCreated();

    Queue::assertPushed(SendConversationMessageJob::class);
});

it('rejects a message with an empty body', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => '',
        ])
        ->assertUnprocessable();
});

it('rejects a body exceeding 1000 characters', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
            'body' => str_repeat('a', 1001),
        ])
        ->assertUnprocessable();
});

it('requires authentication for both endpoints', function () {
    $message = Message::factory()->create();

    $this->getJson("/api/messenger/messages/{$message->getKey()}/conversation")
        ->assertUnauthorized();

    $this->postJson("/api/messenger/messages/{$message->getKey()}/conversation/messages", [
        'body' => 'Unauthenticated',
    ])->assertUnauthorized();
});
