<?php

use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\Reply;
use NettSite\Messenger\Tests\Models\User;

it('stores a reply to a message', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/replies", [
            'body' => 'Thanks for the message!',
        ])
        ->assertCreated();

    expect(
        Reply::where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->where('body', 'Thanks for the message!')
            ->exists()
    )->toBeTrue();
});

it('rejects a reply with an empty body', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/replies", [
            'body' => '',
        ])
        ->assertUnprocessable();
});

it('rejects a reply that exceeds 1000 characters', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/replies", [
            'body' => str_repeat('a', 1001),
        ])
        ->assertUnprocessable();
});

it('requires authentication to reply', function () {
    $message = Message::factory()->create();

    $this->postJson("/api/messenger/messages/{$message->getKey()}/replies", [
        'body' => 'Unauthenticated reply',
    ])->assertUnauthorized();
});

it('returns replies for a message', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $reply = Reply::create([
        'message_id' => $message->getKey(),
        'user_type' => User::class,
        'user_id' => $user->getKey(),
        'body' => 'A reply',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/messenger/messages/{$message->getKey()}/replies")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $reply->getKey());
});

it('requires authentication to list replies', function () {
    $message = Message::factory()->create();

    $this->getJson("/api/messenger/messages/{$message->getKey()}/replies")
        ->assertUnauthorized();
});
