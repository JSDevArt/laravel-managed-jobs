<?php

namespace YourVendor\ManagedJobs\Support;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use YourVendor\ManagedJobs\Contracts\JobChannelResolver;
use YourVendor\ManagedJobs\Models\ManagedJob;

/**
 * Default channel policy: broadcast a job's events on a single channel scoped
 * to its polymorphic owner.
 *
 * Because the owner *type* is part of the channel name by default
 * (e.g. "jobs.user.7" vs "jobs.tenant.7"), owners of different types with
 * matching ids resolve to different channels — the v1 cross-owner collision no
 * longer happens for the common case. The one caveat: without a morph map the
 * type segment is derived from the class basename, so two owner classes that
 * share a basename (App\Models\User vs App\Billing\User) would still collide.
 * Register a morph map to give every owner type a distinct, stable alias and
 * remove that edge entirely.
 *
 * Need more than the owner channel (a tenant channel, a team channel, presence
 * membership, …)? Implement JobChannelResolver in your app and point
 * config('managed-jobs.broadcasting.resolver') at it.
 */
class DefaultJobChannelResolver implements JobChannelResolver
{
    public function channelsFor(ManagedJob $job): array
    {
        if (! config('managed-jobs.broadcasting.enabled', true)) {
            return [];
        }

        return [$this->make($this->channelName($job))];
    }

    /**
     * Build the owner-scoped channel name.
     *
     * include_owner_type = true (default) → "{prefix}.{ownerAlias}.{id}"
     *     Safe for any number of owner types.
     *
     * include_owner_type = false → "{prefix}.{id}"
     *     v1-compatible name. Only safe when every job in the app is owned by a
     *     single owner type; with more than one type the ids share a namespace
     *     again and events can leak across types.
     */
    protected function channelName(ManagedJob $job): string
    {
        $prefix = config('managed-jobs.broadcasting.channel_prefix', 'jobs');

        if (! config('managed-jobs.broadcasting.include_owner_type', true)) {
            return "{$prefix}.{$job->owner_id}";
        }

        return "{$prefix}.{$this->ownerAlias($job)}.{$job->owner_id}";
    }

    /**
     * A short, channel-safe alias for the owner type.
     *
     * Uses the registered morph-map alias when one exists; otherwise derives a
     * clean snake_case alias from the class name so a raw FQCN (with its
     * backslashes) never ends up in a channel name.
     */
    protected function ownerAlias(ManagedJob $job): string
    {
        $type = (string) $job->owner_type;

        // Morph map registered → owner_type is already the short alias.
        if (array_key_exists($type, Relation::morphMap())) {
            return $type;
        }

        return Str::snake(class_basename($type));
    }

    protected function make(string $name): Channel
    {
        return match (config('managed-jobs.broadcasting.channel_type', 'public')) {
            'private'  => new PrivateChannel($name),
            'presence' => new PresenceChannel($name),
            default    => new Channel($name),
        };
    }
}
