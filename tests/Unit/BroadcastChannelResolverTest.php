<?php

namespace YourVendor\ManagedJobs\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\BroadcastChannelResolver;
use YourVendor\ManagedJobs\Tests\TestCase;

class BroadcastChannelResolverTest extends TestCase
{
    /** @return array<int, string> */
    private function channelNames(ManagedJob $job): array
    {
        return array_map(
            static fn (Channel $channel): string => $channel->name,
            BroadcastChannelResolver::forJob($job),
        );
    }

    public function test_returns_only_user_channel_when_no_tenant(): void
    {
        $job = new ManagedJob(['owner_user_id' => 5, 'owner_tenant_id' => null]);

        $this->assertSame(['jobs-user.5'], $this->channelNames($job));
    }

    public function test_appends_tenant_channel_when_tenant_present(): void
    {
        $job = new ManagedJob(['owner_user_id' => 5, 'owner_tenant_id' => 9]);

        $this->assertSame(['jobs-user.5', 'jobs-tenant.9'], $this->channelNames($job));
    }

    /**
     * Regression: a job owned by user 7 and a job owned by tenant 7 must
     * resolve to different channel names, so tenant events never leak onto a
     * user's channel (or vice versa) just because the ids collide numerically.
     */
    public function test_user_and_tenant_channels_never_collide_on_matching_ids(): void
    {
        $userJob = new ManagedJob(['owner_user_id' => 7, 'owner_tenant_id' => null]);
        $tenantJob = new ManagedJob(['owner_user_id' => 99, 'owner_tenant_id' => 7]);

        $userChannels = $this->channelNames($userJob);
        $tenantChannels = $this->channelNames($tenantJob);

        $this->assertSame(['jobs-user.7'], $userChannels);
        $this->assertContains('jobs-tenant.7', $tenantChannels);
        $this->assertNotContains('jobs-user.7', $tenantChannels);
        $this->assertSame([], array_intersect($userChannels, $tenantChannels));
    }

    public function test_returns_empty_when_broadcasting_disabled(): void
    {
        config()->set('managed-jobs.broadcasting.enabled', false);

        $job = new ManagedJob(['owner_user_id' => 5, 'owner_tenant_id' => 9]);

        $this->assertSame([], BroadcastChannelResolver::forJob($job));
    }

    public function test_segments_and_prefix_are_configurable(): void
    {
        config()->set('managed-jobs.broadcasting.channel_prefix', 'mj');
        config()->set('managed-jobs.broadcasting.user_segment', 'u');
        config()->set('managed-jobs.broadcasting.tenant_segment', 't');

        $job = new ManagedJob(['owner_user_id' => 5, 'owner_tenant_id' => 9]);

        $this->assertSame(['mj-u.5', 'mj-t.9'], $this->channelNames($job));
    }

    public function test_channel_type_maps_to_private_channel(): void
    {
        config()->set('managed-jobs.broadcasting.channel_type', 'private');

        $job = new ManagedJob(['owner_user_id' => 5, 'owner_tenant_id' => null]);

        $channels = BroadcastChannelResolver::forJob($job);

        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-jobs-user.5', $channels[0]->name);
    }
}
