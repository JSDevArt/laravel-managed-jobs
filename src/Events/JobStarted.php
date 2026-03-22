<?php

namespace YourVendor\ManagedJobs\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\BroadcastChannelResolver;

class JobStarted implements ShouldBroadcast
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
        return 'job.started';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobRecord->getKey(),
            'type'   => $this->jobRecord->type,
            'status' => $this->jobRecord->status->value,
        ];
    }
}
