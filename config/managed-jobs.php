<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used for job ownership. Must implement
    | YourVendor\ManagedJobs\Contracts\JobOwner.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | User Primary Key Column
    |--------------------------------------------------------------------------
    |
    | The primary key column of your user model.
    | Used by ManagedJob::owner() and ManagedJob::triggeredBy() relationships.
    |
    */
    'user_primary_key' => 'id',

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
    | enabled        – Set to false to disable WebSocket broadcasting entirely.
    |                  Events are still fired for local listeners; only the
    |                  broadcaster is bypassed.
    |
    | channel_prefix – String prepended to every channel name.
    |                  Default 'jobs' produces:  jobs.{userId}  /  jobs.{tenantId}
    |
    | channel_type   – One of: 'public', 'private', 'presence'
    |                  Maps to the matching Illuminate\Broadcasting\* class.
    |                  Use 'private' or 'presence' for authenticated channels.
    |
    */
    'broadcasting' => [
        'enabled'        => true,
        'channel_prefix' => 'jobs',
        'channel_type'   => 'public',  // 'public' | 'private' | 'presence'
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
