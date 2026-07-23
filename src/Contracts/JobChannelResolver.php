<?php

namespace YourVendor\ManagedJobs\Contracts;

use Illuminate\Broadcasting\Channel;
use YourVendor\ManagedJobs\Models\ManagedJob;

/**
 * Resolves the broadcast channels a job's lifecycle events publish on.
 *
 * The package ships DefaultJobChannelResolver and binds it to this contract.
 * Override the binding via config('managed-jobs.broadcasting.resolver') to take
 * full control of where events are broadcast — e.g. to add tenant, team or
 * service channels on top of (or instead of) the owner channel. This is the
 * seam that keeps channel *policy* in the application while the package owns the
 * *mechanism* (firing the events).
 */
interface JobChannelResolver
{
    /**
     * Return the channels the given job's events should broadcast on.
     *
     * Return an empty array to broadcast nowhere (e.g. when broadcasting is
     * disabled). Every element must be an Illuminate broadcasting Channel
     * (Channel, PrivateChannel or PresenceChannel — the latter two extend it).
     *
     * @return array<int, Channel>
     */
    public function channelsFor(ManagedJob $job): array;
}
