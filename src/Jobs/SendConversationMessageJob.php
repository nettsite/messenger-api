<?php

namespace NettSite\Messenger\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NettSite\Messenger\Models\ConversationMessage;
use NettSite\Messenger\Services\FCMService;

class SendConversationMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ConversationMessage $conversationMessage) {}

    public function handle(FCMService $fcm): void
    {
        $conversation = $this->conversationMessage->conversation()->with('message')->first();

        if ($conversation === null) {
            return;
        }

        $user = $conversation->user;

        if ($user === null) {
            return;
        }

        // Do not notify users about their own messages
        if ($this->conversationMessage->author_id === (string) $user->getAuthIdentifier()) {
            return;
        }

        $url = $conversation->message?->url;
        $tokens = $user->deviceTokens()->pluck('token');

        foreach ($tokens as $token) {
            $fcm->send($token, 'New reply', $this->conversationMessage->body, $url);
        }
    }
}
