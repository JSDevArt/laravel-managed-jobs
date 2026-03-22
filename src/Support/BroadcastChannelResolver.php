<?php

namespace YourVendor\ManagedJobs\Support;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use YourVendor\ManagedJobs\Models\ManagedJob;

class BroadcastChannelResolver
{
    /**
     * Build the array of broadcasting channels for a ManagedJob record.
     *
     * Always includes the owner-user channel.
     * Appends a tenant channel when owner_tenant_id is set.
     * Returns an empty array when broadcasting is disabled in config.
     *
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public static function forJob(ManagedJob $job): array
    {
        if (! config('managed-jobs.broadcasting.enabled', true)) {
            return [];
        }

        $channels = [self::make(self::channelName($job->owner_user_id))];

        if (! is_null($job->owner_tenant_id)) {
            $channels[] = self::make(self::channelName($job->owner_tenant_id));
        }

        return $channels;
    }

    private static function channelName(int|string $id): string
    {
        $prefix = config('managed-jobs.broadcasting.channel_prefix', 'jobs');

        return "{$prefix}.{$id}";
    }

    private static function make(string $name): Channel|PrivateChannel|PresenceChannel
    {
        $type = config('managed-jobs.broadcasting.channel_type', 'public');

        return match ($type) {
            'private'  => new PrivateChannel($name),
            'presence' => new PresenceChannel($name),
            default    => new Channel($name),
        };
    }
}
