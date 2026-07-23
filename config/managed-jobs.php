<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default File Expiry
    |--------------------------------------------------------------------------
    |
    | Number of days before a job-generated file is considered expired and
    | deleted by the managed-jobs:expire-files command.
    |
    */
    'file_expiry_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Optional prefix applied to the managed_jobs and managed_job_files table
    | names. Useful when sharing a database that already uses those names.
    | Leave empty (default) for no prefix.
    |
    | NOTE: If you change this, publish the migrations (managed-jobs-migrations)
    | and rename the tables in the migration files to match before running
    | php artisan migrate.
    |
    | Example: 'table_prefix' => 'bg_'  ->  bg_managed_jobs, bg_managed_job_files
    |
    */
    'table_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | enabled            – Set to false to disable WebSocket broadcasting
    |                      entirely. Events still fire for local listeners; only
    |                      the broadcaster is bypassed.
    |
    | resolver           – Class implementing
    |                      YourVendor\ManagedJobs\Contracts\JobChannelResolver.
    |                      Decides which channels a job's events broadcast on.
    |                      Point this at your own class to add tenant / team /
    |                      service channels or change the naming entirely — that
    |                      is where app-specific channel policy belongs. The
    |                      default broadcasts one channel scoped to the owner.
    |
    | channel_prefix     – String prepended to every channel name (default resolver).
    |
    | include_owner_type – When true (default) the owner type is baked into the
    |                      channel name: jobs.user.5 / jobs.tenant.5. This makes
    |                      it impossible for two owners of different types to
    |                      collide on the same channel.
    |                      Set to false for v1-style flat names (jobs.5) — ONLY
    |                      safe when every job is owned by a single owner type.
    |
    | channel_type       – One of: 'public', 'private', 'presence'. Maps to the
    |                      matching Illuminate\Broadcasting\* class. Use 'private'
    |                      or 'presence' for authenticated channels.
    |
    */
    'broadcasting' => [
        'enabled'            => true,
        'resolver'           => \YourVendor\ManagedJobs\Support\DefaultJobChannelResolver::class,
        'channel_prefix'     => 'jobs',
        'include_owner_type' => true,
        'channel_type'       => 'public',  // 'public' | 'private' | 'presence'
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | connection – The queue connection to use (e.g. 'redis', 'sqs', 'sync').
    |              null = Laravel's default connection.
    |
    | name       – The queue name/channel within that connection.
    |              null = the connection's default queue.
    |
    | Both settings can be overridden per-dispatch via JobRunner::dispatch()
    | $queue and $connection parameters.
    |
    */
    'queue' => [
        'connection' => null,
        'name'       => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | disk – The Laravel filesystem disk used when reading/deleting physical
    |        job files (e.g. in ExpireJobFilesCommand and the download endpoint).
    |        null = Laravel's default disk.
    |
    */
    'storage' => [
        'disk' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | enabled         – When false, the ServiceProvider will not register the
    |                   managed-jobs:expire-files command in the scheduler.
    |                   You are responsible for running the command yourself.
    |
    | expire_files_at – dailyAt() time string (24-hour format), e.g. '22:00'.
    |
    */
    'schedule' => [
        'enabled'             => true,
        'expire_files_at'     => '22:00',

        // Prevent overlapping executions. Set to false to disable, or an
        // integer for the maximum lock duration in minutes (default: 5).
        'without_overlapping' => 5,

        // Restrict execution to a single server in multi-server deployments.
        // Requires a cache driver that supports atomic locks (Redis, database).
        'on_one_server'       => true,

        // Run the command in the background so the scheduler itself is not blocked.
        'run_in_background'   => true,
    ],

];
