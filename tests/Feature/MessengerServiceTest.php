<?php

use Illuminate\Support\Facades\Queue;
use NettSite\Messenger\Jobs\SendMessageJob;
use NettSite\Messenger\Messenger;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessageRecipient;

it('broadcast creates a message and a recipient record', function () {
    $messenger = app(Messenger::class);
    $message = $messenger->broadcast('Hello everyone!', null, 'all', null);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Hello everyone!')
        ->and($message->exists)->toBeTrue();

    expect(
        MessageRecipient::where('message_id', $message->getKey())
            ->where('recipient_type', 'all')
            ->whereNull('recipient_id')
            ->exists()
    )->toBeTrue();
});

it('broadcast stores an optional url', function () {
    $messenger = app(Messenger::class);
    $message = $messenger->broadcast('Check this out', 'https://example.com', 'all', null);

    expect($message->url)->toBe('https://example.com');
});

it('send dispatches SendMessageJob', function () {
    Queue::fake();

    $message = Message::factory()->create();
    app(Messenger::class)->send($message);

    Queue::assertPushed(SendMessageJob::class, fn ($job) => $job->message->is($message));
});

it('schedule saves scheduled_at on the message', function () {
    $scheduledAt = now()->addHour();
    $messenger = app(Messenger::class);

    $message = $messenger->broadcast('Scheduled message', null, 'all', null);
    $message->scheduled_at = $scheduledAt;
    $messenger->schedule($message);

    $message->refresh();
    expect($message->scheduled_at)->not->toBeNull();
});
