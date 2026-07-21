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
     * The user and tenant channels use distinct name segments
     * (owner-user vs owner-tenant) so their numeric ids can never collide:
     * a job whose owner_user_id equals another job's owner_tenant_id still
     * resolves to two different channels.
     *
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public static function forJob(ManagedJob $job): array
    {
        if (! config('managed-jobs.broadcasting.enabled', true)) {
            return [];
        }

        $channels = [self::make(self::userChannelName($job->owner_user_id))];

        if (! is_null($job->owner_tenant_id)) {
            $channels[] = self::make(self::tenantChannelName($job->owner_tenant_id));
        }

        return $channels;
    }

    /**
     * Channel name scoped to the owning user, e.g. "jobs-user.{id}".
     */
    public static function userChannelName(int|string $id): string
    {
        $segment = config('managed-jobs.broadcasting.user_segment', 'user');

        return self::channelName($segment, $id);
    }

    /**
     * Channel name scoped to the owning tenant, e.g. "jobs-tenant.{id}".
     */
    public static function tenantChannelName(int|string $id): string
    {
        $segment = config('managed-jobs.broadcasting.tenant_segment', 'tenant');

        return self::channelName($segment, $id);
    }

    private static function channelName(string $segment, int|string $id): string
    {
        $prefix = config('managed-jobs.broadcasting.channel_prefix', 'jobs');

        return "{$prefix}-{$segment}.{$id}";
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
