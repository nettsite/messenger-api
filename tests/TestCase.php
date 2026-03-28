<?php

namespace NettSite\Messenger\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\SanctumServiceProvider;
use NettSite\Messenger\MessengerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NettSite\\Messenger\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
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

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }

        foreach (File::glob(__DIR__.'/../vendor/laravel/sanctum/database/migrations/*.php') as $file) {
            (include $file)->up();
        }
    }
}
