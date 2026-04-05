<?php

namespace NettSite\Messenger;

use Illuminate\Support\Facades\Route;
use NettSite\Messenger\Commands\InstallCommand;
use NettSite\Messenger\Commands\MessengerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MessengerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('messenger')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_messenger_device_tokens_table',
                'create_messenger_groups_table',
                'create_messenger_group_users_table',
                'create_messenger_messages_table',
                'create_messenger_message_recipients_table',
                'create_messenger_message_receipts_table',
                'create_messenger_replies_table',
            ])
            ->runsMigrations()
            ->hasCommand(MessengerCommand::class)
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');
    }
}
