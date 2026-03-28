<?php

namespace NettSite\Messenger\Filament\Resources\MessageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use NettSite\Messenger\Filament\Resources\MessageResource;
use NettSite\Messenger\Messenger;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $messenger = app(Messenger::class);

        $message = $messenger->broadcast(
            body: $data['body'],
            url: $data['url'] ?? null,
            recipientType: $data['recipient_type'],
            recipientId: $data['recipient_id'] ?? null,
        );

        if (! empty($data['scheduled_at'])) {
            $message->scheduled_at = $data['scheduled_at'];
            $messenger->schedule($message);
        } else {
            $messenger->send($message);
        }

        return $message;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
