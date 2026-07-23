<?php

namespace YourVendor\ManagedJobs\Support;

use YourVendor\ManagedJobs\Contracts\JobChannelResolver;
use YourVendor\ManagedJobs\Models\ManagedJob;

/**
 * Thin static entry point the lifecycle events call from broadcastOn().
 *
 * The actual channel policy lives in the bound JobChannelResolver
 * (DefaultJobChannelResolver unless overridden via
 * config('managed-jobs.broadcasting.resolver')). Keeping this facade means the
 * events don't need to reach into the container themselves, and existing
 * `BroadcastChannelResolver::forJob()` call sites keep working unchanged.
 */
class BroadcastChannelResolver
{
    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public static function forJob(ManagedJob $job): array
    {
        return app(JobChannelResolver::class)->channelsFor($job);
    }
}
