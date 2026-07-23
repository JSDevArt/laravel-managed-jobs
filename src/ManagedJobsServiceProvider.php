<?php

namespace YourVendor\ManagedJobs;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use YourVendor\ManagedJobs\Console\Commands\ExpireJobFilesCommand;
use YourVendor\ManagedJobs\Contracts\JobChannelResolver;
use YourVendor\ManagedJobs\Support\DefaultJobChannelResolver;

class ManagedJobsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/managed-jobs.php',
            'managed-jobs',
        );

        $this->registerChannelResolver();
    }

    /**
     * Bind the channel resolver. Apps override the whole channel policy by
     * pointing config('managed-jobs.broadcasting.resolver') at their own
     * JobChannelResolver implementation; otherwise the safe default is used.
     */
    private function registerChannelResolver(): void
    {
        $this->app->bind(JobChannelResolver::class, function ($app) {
            $class = config('managed-jobs.broadcasting.resolver', DefaultJobChannelResolver::class);

            return $app->make($class);
        });
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->loadAndPublishMigrations();
        $this->registerCommands();
        $this->scheduleExpiry();
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/managed-jobs.php' => config_path('managed-jobs.php'),
        ], 'managed-jobs-config');
    }

    private function loadAndPublishMigrations(): void
    {
        // Migrations run automatically with `php artisan migrate` — no publish needed.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Optionally publish them if the app needs to customize the schema.
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'managed-jobs-migrations');

        // v1 → v2 upgrade migration. Opt-in (publish + edit + migrate); NOT
        // loaded automatically because it rewrites existing columns and needs
        // the app to confirm the owner backfill first. See UPGRADE.md.
        $this->publishes([
            __DIR__ . '/../database/upgrades' => database_path('migrations'),
        ], 'managed-jobs-upgrades');
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireJobFilesCommand::class,
            ]);
        }
    }

    private function scheduleExpiry(): void
    {
        if (! config('managed-jobs.schedule.enabled', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $time    = config('managed-jobs.schedule.expire_files_at', '22:00');
            $event   = $schedule->command('managed-jobs:expire-files')->dailyAt($time);

            $overlap = config('managed-jobs.schedule.without_overlapping', false);
            if ($overlap !== false) {
                $event->withoutOverlapping(is_int($overlap) ? $overlap : 5);
            }

            if (config('managed-jobs.schedule.on_one_server', false)) {
                $event->onOneServer();
            }

            if (config('managed-jobs.schedule.run_in_background', false)) {
                $event->runInBackground();
            }
        });
    }
}
