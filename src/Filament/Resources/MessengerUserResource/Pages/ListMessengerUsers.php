<?php

namespace NettSite\Messenger\Filament\Resources\MessengerUserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use NettSite\Messenger\Filament\Resources\MessengerUserResource;

class ListMessengerUsers extends ListRecords
{
    protected static string $resource = MessengerUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
