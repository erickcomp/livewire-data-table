<?php

namespace Tests;

use ErickComp\LivewireDataTable\ServiceProvider as LivewireDataTableServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;


class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            LivewireDataTableServiceProvider::class,
        ];
    }
}
