<?php

namespace NettSite\Messenger\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use NettSite\Messenger\Filament\Resources\GroupResource;
use NettSite\Messenger\Filament\Resources\MessageResource;

class MessengerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'messenger';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            GroupResource::class,
            MessageResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
