<?php

namespace YourVendor\ManagedJobs\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Relations\Relation;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\BroadcastChannelResolver;
use YourVendor\ManagedJobs\Tests\TestCase;

class DefaultJobChannelResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    /**
     * Resolve channel names through the public facade the events use, so the
     * container binding (config resolver → DefaultJobChannelResolver) is
     * exercised end to end.
     *
     * @return array<int, string>
     */
    private function channelNames(ManagedJob $job): array
    {
        return array_map(
            static fn (Channel $channel): string => $channel->name,
            BroadcastChannelResolver::forJob($job),
        );
    }

    private function job(string $ownerType, int|string $ownerId): ManagedJob
    {
        return new ManagedJob(['owner_type' => $ownerType, 'owner_id' => $ownerId]);
    }

    public function test_owner_channel_includes_the_owner_type_by_default(): void
    {
        $this->assertSame(['jobs.user.7'], $this->channelNames($this->job('user', 7)));
    }

    public function test_fqcn_owner_type_is_reduced_to_a_clean_alias(): void
    {
        $this->assertSame(
            ['jobs.user.7'],
            $this->channelNames($this->job('App\\Models\\User', 7)),
        );
    }

    /**
     * Regression for the v1 collision bug: a job owned by user 7 and a job
     * owned by tenant 7 must resolve to different channels. Under the
     * polymorphic default this is structural — the type is part of the name.
     */
    public function test_owners_of_different_types_never_collide_on_matching_ids(): void
    {
        $userChannels   = $this->channelNames($this->job('user', 7));
        $tenantChannels = $this->channelNames($this->job('tenant', 7));

        $this->assertSame(['jobs.user.7'], $userChannels);
        $this->assertSame(['jobs.tenant.7'], $tenantChannels);
        $this->assertSame([], array_intersect($userChannels, $tenantChannels));
    }

    public function test_registered_morph_alias_is_used_verbatim(): void
    {
        Relation::morphMap(['svc' => \YourVendor\ManagedJobs\Models\ManagedJob::class], false);

        $this->assertSame(['jobs.svc.7'], $this->channelNames($this->job('svc', 7)));
    }

    public function test_flat_v1_names_when_owner_type_excluded(): void
    {
        config()->set('managed-jobs.broadcasting.include_owner_type', false);

        $this->assertSame(['jobs.7'], $this->channelNames($this->job('user', 7)));
    }

    public function test_returns_empty_when_broadcasting_disabled(): void
    {
        config()->set('managed-jobs.broadcasting.enabled', false);

        $this->assertSame([], BroadcastChannelResolver::forJob($this->job('user', 7)));
    }

    public function test_prefix_is_configurable(): void
    {
        config()->set('managed-jobs.broadcasting.channel_prefix', 'mj');

        $this->assertSame(['mj.user.7'], $this->channelNames($this->job('user', 7)));
    }

    public function test_channel_type_maps_to_private_channel(): void
    {
        config()->set('managed-jobs.broadcasting.channel_type', 'private');

        $channels = BroadcastChannelResolver::forJob($this->job('user', 7));

        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-jobs.user.7', $channels[0]->name);
    }

    public function test_channel_type_maps_to_presence_channel(): void
    {
        config()->set('managed-jobs.broadcasting.channel_type', 'presence');

        $channels = BroadcastChannelResolver::forJob($this->job('user', 7));

        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
        $this->assertSame('presence-jobs.user.7', $channels[0]->name);
    }
}
