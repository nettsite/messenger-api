<?php

namespace NettSite\Messenger\Filament\Resources\MessengerUserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use NettSite\Messenger\Filament\Resources\MessengerUserResource;

class EditMessengerUser extends EditRecord
{
    protected static string $resource = MessengerUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
