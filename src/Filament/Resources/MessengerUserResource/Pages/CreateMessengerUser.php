<?php

namespace NettSite\Messenger\Filament\Resources\MessengerUserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use NettSite\Messenger\Filament\Resources\MessengerUserResource;

class CreateMessengerUser extends CreateRecord
{
    protected static string $resource = MessengerUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
