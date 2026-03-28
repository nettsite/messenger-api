<?php

namespace NettSite\Messenger\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use NettSite\Messenger\Models\DeviceToken;
use NettSite\Messenger\Models\Message;

interface MessengerAuthenticatable extends Authenticatable
{
    public function messageReceipts(): MorphMany;

    public function groups(): MorphToMany;

    public function deviceTokens(): MorphMany;

    public function registerDeviceToken(string $token, string $platform): DeviceToken;

    public function markMessageRead(Message $message): void;
}
