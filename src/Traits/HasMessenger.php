<?php

namespace NettSite\Messenger\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\Group;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessageReceipt;
use NettSite\Messenger\Models\MessengerEnrollment;

trait HasMessenger
{
    public function messengerEnrollment(): MorphOne
    {
        return $this->morphOne(MessengerEnrollment::class, 'user');
    }

    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'user');
    }

    public function groups(): MorphToMany
    {
        return $this->morphToMany(Group::class, 'user', 'messenger_group_users');
    }

    public function messageReceipts(): MorphMany
    {
        return $this->morphMany(MessageReceipt::class, 'user');
    }

    public function registerDeviceToken(string $token, string $platform): DeviceToken
    {
        // Search globally (not scoped to this user) so the token is atomically reassigned
        // if it was previously registered to a different user on the same device.
        /** @var DeviceToken $deviceToken */
        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_type' => $this->getMorphClass(),
                'user_id' => $this->getKey(),
                'platform' => $platform,
                'last_seen_at' => now(),
            ],
        );

        return $deviceToken;
    }

    public function markMessageRead(Message $message): void
    {
        $this->messageReceipts()->updateOrCreate(
            ['message_id' => $message->getKey()],
            ['read_at' => now()],
        );
    }
}
