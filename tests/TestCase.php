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
}
