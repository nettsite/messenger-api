<?php

namespace NettSite\Messenger\Filament\Resources\MessageResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use NettSite\Messenger\Filament\Resources\MessageResource;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
