<?php

namespace NettSite\Messenger\Filament\Resources\GroupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use NettSite\Messenger\Filament\Resources\GroupResource;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
