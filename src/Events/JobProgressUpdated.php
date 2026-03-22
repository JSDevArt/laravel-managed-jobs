<?php

namespace YourVendor\ManagedJobs\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\BroadcastChannelResolver;

class JobProgressUpdated implements ShouldBroadcast
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
        return 'job.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobRecord->getKey(),
            'progress' => (int) ($this->jobRecord->progress_percentage ?? 0),
            'progress_message' => (string) ($this->jobRecord->progress_message ?? ''),
        ];
    }
}
