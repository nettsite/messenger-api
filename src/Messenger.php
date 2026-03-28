<?php

namespace NettSite\Messenger;

use NettSite\Messenger\Jobs\SendMessageJob;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessageRecipient;

class Messenger
{
    public function broadcast(string $body, ?string $url, string $recipientType, ?string $recipientId): Message
    {
        $message = Message::create([
            'body' => $body,
            'url' => $url,
        ]);

        MessageRecipient::create([
            'message_id' => $message->getKey(),
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
        ]);

        return $message;
    }

    public function send(Message $message): void
    {
        SendMessageJob::dispatch($message);
    }

    public function schedule(Message $message): void
    {
        $message->save();
    }
}
