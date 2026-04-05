<?php

namespace NettSite\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NettSite\Messenger\Traits\HasMessenger;

class InstallCommand extends Command
{
    public $signature = 'messenger:install';

    public $description = 'Install and configure the Messenger package';

    public function handle(): int
    {
        $this->info('Installing Messenger...');
        $this->newLine();

        $this->publishConfig();
        $this->publishMigrations();
        $this->offerToMigrate();
        $this->checkHasMessengerTrait();
        $this->printPluginInstructions();

        $this->newLine();
        $this->info('Messenger installed successfully.');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        if (File::exists(config_path('messenger.php'))) {
            if (! $this->confirm('config/messenger.php already exists. Overwrite?', false)) {
                $this->line('  Skipping config publish.');

                return;
            }
        }

        $this->callSilently('vendor:publish', [
            '--tag' => 'messenger-config',
            '--force' => true,
        ]);

        $this->line('  <info>Published</info> config/messenger.php');
    }

    private function publishMigrations(): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'messenger-migrations',
        ]);

        $this->line('  <info>Published</info> messenger migrations to database/migrations/');
    }

    private function offerToMigrate(): void
    {
        if ($this->confirm('Run database migrations now?', true)) {
            $this->call('migrate');
        }
    }

    private function checkHasMessengerTrait(): void
    {
        /** @var class-string $userModel */
        $userModel = config('messenger.user_model', 'App\Models\User');

        $this->newLine();
        $this->line("  Checking <comment>{$userModel}</comment> for HasMessenger trait...");

        if (class_exists($userModel)) {
            $traits = class_uses_recursive($userModel);

            if (in_array(HasMessenger::class, $traits, true)) {
                $this->line('  <info>HasMessenger trait already present.</info>');

                return;
            }
        }

        $this->warn("  HasMessenger trait not detected on {$userModel}.");
        $this->newLine();
        $this->line('  Add the following import to your User model:');
        $this->newLine();
        $this->line('    use NettSite\Messenger\Traits\HasMessenger;');
        $this->newLine();
        $this->line('  And add the trait inside the class:');
        $this->newLine();
        $this->line('    use HasMessenger;');
    }

    private function printPluginInstructions(): void
    {
        $this->newLine();
        $this->line('  Add the Messenger plugin to your Filament panel provider:');
        $this->newLine();
        $this->line('    use NettSite\Messenger\Filament\MessengerPlugin;');
        $this->newLine();
        $this->line('    ->plugin(MessengerPlugin::make())');
    }
}
