<?php

use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\Group;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessageReceipt;
use NettSite\Messenger\Models\MessageRecipient;
use NettSite\Messenger\Tests\Models\User;

it('can create a message with a sender', function () {
    $sender = User::factory()->create();
    $message = Message::factory()->create([
        'sender_type' => User::class,
        'sender_id' => $sender->getKey(),
    ]);

    expect($message->sender->is($sender))->toBeTrue()
        ->and($message->body)->not->toBeEmpty();
});

it('can create a group and attach users', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->users()->attach($user->getKey(), ['user_type' => User::class]);

    expect($group->users()->count())->toBe(1);
});

it('can register a device token via HasMessenger', function () {
    $user = User::factory()->create();

    $token = $user->registerDeviceToken('fcm-token-abc', 'android');

    expect($token->token)->toBe('fcm-token-abc')
        ->and($token->platform)->toBe('android')
        ->and($token->last_seen_at)->not->toBeNull();
});

it('can mark a message as read via HasMessenger', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create();

    $user->markMessageRead($message);

    $receipt = MessageReceipt::where('message_id', $message->getKey())
        ->where('user_type', User::class)
        ->where('user_id', $user->getKey())
        ->first();

    expect($receipt)->not->toBeNull()
        ->and($receipt->read_at)->not->toBeNull();
});

it('can create a device token via factory', function () {
    $token = DeviceToken::factory()->create();

    expect($token->id)->toBeString()
        ->and($token->token)->not->toBeEmpty();
});

it('tracks read and recipient counts on a message', function () {
    $sender = User::factory()->create();
    $message = Message::factory()->create([
        'sender_type' => User::class,
        'sender_id' => $sender->getKey(),
    ]);

    MessageRecipient::create([
        'message_id' => $message->getKey(),
        'recipient_type' => 'all',
        'recipient_id' => null,
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->markMessageRead($message);

    // recipientCount resolves intended audience: 'all' expands to total user count.
    // The sender + userA + userB = 3 users exist; all three are included.
    expect($message->recipientCount())->toBe(User::count())
        ->and($message->readCount())->toBe(1);
});
