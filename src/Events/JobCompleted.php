<?php

namespace YourVendor\ManagedJobs\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\BroadcastChannelResolver;

class JobCompleted implements ShouldBroadcast
{
    public function __construct(
        public readonly ManagedJob $jobRecord
    ) {}

    public function broadcastOn(): array
    {
        return BroadcastChannelResolver::forJob($this->jobRecord);
    }

    public function broadcastAs(): string
    {
        return 'job.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobRecord->getKey(),
            'status' => $this->jobRecord->status->value,
        ];
    }
}
