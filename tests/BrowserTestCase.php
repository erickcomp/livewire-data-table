<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for the browser tests in tests/Browser.
 *
 * Boots the workbench app (testbench.yaml): the package providers, the demo routes and the seeded
 * products. Pest's browser plugin serves this same app in-process, so the in-memory database is shared.
 *
 * The Browser test suite is left out of the default ones (phpunit.xml) because it needs Node and
 * Playwright: run it with "composer test:browser".
 */
abstract class BrowserTestCase extends Orchestra
{
    use RefreshDatabase;
    use WithWorkbench;

    protected bool $seed = true;

    protected string $seeder = \Workbench\Database\Seeders\DatabaseSeeder::class;

    /**
     * phpunit.xml keeps the session in an array, which the browser's requests do not share: a cookie carries it
     * from one request to the next, as remember-state needs
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('session.driver', 'cookie');

        // Set before the routes are read (see WithoutContentLength)
        $app['config']->set('erickcomp-livewire-data-table.export.route.middleware', ['web', \Tests\Fixtures\WithoutContentLength::class]);
    }
}
