<?php

namespace NettSite\Messenger\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NettSite\Messenger\Models\Message;

class ProcessScheduledMessagesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Message::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->each(fn (Message $message) => SendMessageJob::dispatch($message));
    }
}
