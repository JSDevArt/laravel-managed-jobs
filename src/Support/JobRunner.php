<?php

namespace YourVendor\ManagedJobs\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use YourVendor\ManagedJobs\Contracts\JobOwner;
use YourVendor\ManagedJobs\Contracts\JobPayload;
use YourVendor\ManagedJobs\Enums\JobStatusEnum;
use YourVendor\ManagedJobs\Jobs\BaseJob;
use YourVendor\ManagedJobs\Models\ManagedJob;

class JobRunner
{
    /**
     * Dispatch a managed background job.
     *
     * The ManagedJob record is created synchronously inside a transaction.
     * The actual queue dispatch happens after the transaction commits to avoid
     * the worker picking up the job before the DB record is visible.
     *
     * @param  class-string<BaseJob>  $job          Fully qualified class name of the job.
     * @param  JobPayload             $payload      Serializable payload for the job.
     * @param  JobOwner               $owner        The user who owns this job execution.
     * @param  JobOwner|null          $triggeredBy  Optional: admin/system acting on behalf of owner.
     * @param  string|null            $queue        Override the queue name for this dispatch only.
     *                                              null = use global config('managed-jobs.queue.name').
     * @param  string|null            $connection   Override the queue connection for this dispatch only.
     *                                              null = use global config('managed-jobs.queue.connection').
     *
     * @throws InvalidArgumentException if $job does not extend BaseJob.
     */
    public static function dispatch(
        string $job,
        JobPayload $payload,
        JobOwner $owner,
        ?JobOwner $triggeredBy = null,
        ?string $queue = null,
        ?string $connection = null,
    ): ManagedJob {
        if (! is_subclass_of($job, BaseJob::class)) {
            throw new InvalidArgumentException(
                "The class [{$job}] must extend " . BaseJob::class
            );
        }

        return DB::transaction(function () use ($job, $payload, $owner, $triggeredBy, $queue, $connection) {
            $jobExecution = ManagedJob::create([
                'type'                 => $job,
                'status'               => JobStatusEnum::PENDING,
                'payload'              => $payload->toArray(),
                'owner_user_id'        => $owner->getManagedJobOwnerId(),
                'owner_tenant_id'      => $owner->getManagedJobTenantId(),
                'triggered_by_user_id' => $triggeredBy?->getManagedJobOwnerId(),
            ]);

            DB::afterCommit(function () use ($job, $jobExecution, $queue, $connection) {
                $pending = $job::dispatch($jobExecution);

                // Per-dispatch overrides supersede global config defaults that
                // BaseJob::__construct() already applied via onConnection/onQueue.
                if ($connection !== null) {
                    $pending->onConnection($connection);
                }

                if ($queue !== null) {
                    $pending->onQueue($queue);
                }
            });

            return $jobExecution;
        });
    }
}
