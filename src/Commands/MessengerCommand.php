<?php

namespace NettSite\Messenger\Commands;

use Illuminate\Console\Command;
use NettSite\Messenger\Jobs\ProcessScheduledMessagesJob;

class MessengerCommand extends Command
{
    public $signature = 'messenger:send-scheduled';

    public $description = 'Dispatch scheduled messages that are due to be sent';

    public function handle(): int
    {
        ProcessScheduledMessagesJob::dispatch();

        $this->comment('Scheduled messages queued for sending.');

        return self::SUCCESS;
    }
}
