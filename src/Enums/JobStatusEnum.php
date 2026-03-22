<?php

namespace YourVendor\ManagedJobs\Enums;

enum JobStatusEnum: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case STOPPED = 'stopped';
}
