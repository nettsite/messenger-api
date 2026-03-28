<?php

namespace NettSite\Messenger\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use NettSite\Messenger\Filament\Resources\GroupResource;
use NettSite\Messenger\Filament\Resources\MessageResource;
use NettSite\Messenger\Filament\Resources\MessengerUserResource;

class MessengerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(config('messenger.panel.id', 'messenger'))
            ->path(config('messenger.panel.path', 'messenger'))
            ->login()
            ->authGuard(config('messenger.panel.guard', 'web'))
            ->resources([
                MessengerUserResource::class,
                GroupResource::class,
                MessageResource::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
