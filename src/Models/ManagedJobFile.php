<?php

namespace YourVendor\ManagedJobs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManagedJobFile extends Model
{
    use SoftDeletes;

    public function getTable(): string
    {
        return config('managed-jobs.table_prefix', '') . 'managed_job_files';
    }

    protected $primaryKey = 'job_file_id';

    protected $fillable = [
        'job_id',
        'filename',
        'path',
        'mime_type',
        'size_bytes',
        'expires_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ManagedJob::class, 'job_id', (new ManagedJob)->getKeyName());
    }

    /**
     * Scope to only include non-expired files.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
