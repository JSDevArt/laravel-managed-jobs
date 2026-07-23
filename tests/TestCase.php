<?php

namespace YourVendor\ManagedJobs\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YourVendor\ManagedJobs\ManagedJobsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ManagedJobsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
