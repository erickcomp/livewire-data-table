<?php

namespace Tests;

use ErickComp\LivewireDataTable\ServiceProvider as LivewireDataTableServiceProvider;
use ErickComp\RawBladeComponents\RawBladeComponentsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            RawBladeComponentsServiceProvider::class,
            LivewireDataTableServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
