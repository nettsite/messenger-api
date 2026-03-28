<?php

namespace NettSite\Messenger\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Services\FCMService;

class SendMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Message $message) {}

    public function handle(FCMService $fcm): void
    {
        $tokens = $this->resolveDeviceTokens();

        foreach ($tokens as $token) {
            $fcm->send($token->token, 'New message', $this->message->body, $this->message->url);
        }

        $this->message->update(['sent_at' => now()]);
    }

    /** @return Collection<int, DeviceToken> */
    private function resolveDeviceTokens(): Collection
    {
        $recipients = $this->message->recipients;

        $tokens = new Collection;

        foreach ($recipients as $recipient) {
            $tokens = $tokens->merge(
                match ($recipient->recipient_type) {
                    'all' => DeviceToken::all(),
                    'user' => DeviceToken::where('user_id', $recipient->recipient_id)->get(),
                    'group' => $this->tokensForGroup($recipient->recipient_id),
                    default => new Collection,
                }
            );
        }

        return $tokens->unique('id');
    }

    /** @return Collection<int, DeviceToken> */
    private function tokensForGroup(string $groupId): Collection
    {
        $members = DB::table('messenger_group_users')
            ->where('group_id', $groupId)
            ->get(['user_type', 'user_id']);

        if ($members->isEmpty()) {
            return new Collection;
        }

        return DeviceToken::where(function ($query) use ($members) {
            foreach ($members as $member) {
                $query->orWhere(function ($q) use ($member) {
                    $q->where('user_type', $member->user_type)
                        ->where('user_id', $member->user_id);
                });
            }
        })->get();
    }
}
