<?php

namespace Djouamaah\Algeria\Tests;

use Djouamaah\Algeria\AlgeriaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AlgeriaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('algeria.routes.enabled', true);
    }
}
