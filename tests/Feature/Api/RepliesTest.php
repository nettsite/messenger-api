<?php

use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessengerUser;
use NettSite\Messenger\Models\Reply;

it('stores a reply to a message', function () {
    $user = MessengerUser::factory()->create();
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
    $user = MessengerUser::factory()->create();
    $message = Message::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/messenger/messages/{$message->getKey()}/replies", [
            'body' => '',
        ])
        ->assertUnprocessable();
});

it('rejects a reply that exceeds 1000 characters', function () {
    $user = MessengerUser::factory()->create();
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
