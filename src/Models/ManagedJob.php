<?php

namespace YourVendor\ManagedJobs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use YourVendor\ManagedJobs\Enums\JobStatusEnum;

class ManagedJob extends Model
{
    use SoftDeletes;

    public function getTable(): string
    {
        return config('managed-jobs.table_prefix', '') . 'managed_jobs';
    }

    protected $primaryKey = 'job_id';

    protected $fillable = [
        'type',
        'status',
        'payload',
        'state',
        'progress_percentage',
        'progress_message',
        'owner_user_id',
        'owner_tenant_id',
        'triggered_by_user_id',
        'started_at',
        'finished_at',
        'failed_reason',
    ];

    protected $casts = [
        'status' => JobStatusEnum::class,
        'payload' => 'array',
        'state' => 'array',
        'progress_percentage' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The user who owns this job.
     * Resolved from config('managed-jobs.user_model').
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            config('managed-jobs.user_model'),
            'owner_user_id',
            config('managed-jobs.user_primary_key', 'id'),
        );
    }

    /**
     * The user who triggered this job (e.g. an admin acting on behalf of the owner).
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(
            config('managed-jobs.user_model'),
            'triggered_by_user_id',
            config('managed-jobs.user_primary_key', 'id'),
        );
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedJobFile::class, 'job_id', $this->getKeyName());
    }
}
