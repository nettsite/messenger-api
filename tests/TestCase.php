<?php

namespace NettSite\Messenger\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\SanctumServiceProvider;
use NettSite\Messenger\MessengerServiceProvider;
use NettSite\Messenger\Tests\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if (str_starts_with($modelName, 'NettSite\\Messenger\\Tests\\')) {
                return 'NettSite\\Messenger\\Tests\\Factories\\'.class_basename($modelName).'Factory';
            }

            return 'NettSite\\Messenger\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            MessengerServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('messenger.user_model', User::class);

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }

        foreach (File::glob(__DIR__.'/../vendor/laravel/sanctum/database/migrations/*.php') as $file) {
            (include $file)->up();
        }
    }
}
