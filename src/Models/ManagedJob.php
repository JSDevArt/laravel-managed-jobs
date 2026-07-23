<?php

namespace YourVendor\ManagedJobs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        'owner_type',
        'owner_id',
        'triggered_by_type',
        'triggered_by_id',
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
     * The model that owns this job (any Eloquent model — user, tenant, service…).
     *
     * Resolved polymorphically from owner_type / owner_id, so the package stays
     * agnostic about what an "owner" is. Register a morph map in your app to
     * store short aliases ('user', 'service') instead of full class names.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The model that triggered this job (e.g. an admin acting on behalf of the
     * owner). Also polymorphic; null when the owner triggered it themselves.
     */
    public function triggeredBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedJobFile::class, 'job_id', $this->getKeyName());
    }

    /**
     * Scope jobs to those owned by the given model.
     *
     * Replaces the v1 `where('owner_user_id', $user->getManagedJobOwnerId())`
     * idiom: ManagedJob::ownedBy($request->user())->get().
     */
    public function scopeOwnedBy(Builder $query, Model $owner): Builder
    {
        return $query->whereMorphedTo('owner', $owner);
    }
}
