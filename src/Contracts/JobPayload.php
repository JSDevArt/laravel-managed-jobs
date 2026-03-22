<?php

namespace YourVendor\ManagedJobs\Contracts;

/**
 * Contract for job payloads.
 *
 * Any class with a toArray() method satisfies this interface.
 * This means Spatie\LaravelData\Data objects work out of the box
 * without requiring spatie/laravel-data as a package dependency.
 *
 * Example using plain PHP:
 *
 *   class MyJobPayload implements JobPayload
 *   {
 *       public function __construct(
 *           public readonly string $dateFrom,
 *           public readonly string $dateTo,
 *       ) {}
 *
 *       public function toArray(): array
 *       {
 *           return ['date_from' => $this->dateFrom, 'date_to' => $this->dateTo];
 *       }
 *   }
 *
 * Example using spatie/laravel-data:
 *
 *   class MyJobPayload extends \Spatie\LaravelData\Data implements JobPayload
 *   {
 *       public function __construct(
 *           public readonly string $dateFrom,
 *           public readonly string $dateTo,
 *       ) {}
 *   }
 */
interface JobPayload
{
    /**
     * Serialize the payload to an array for DB storage.
     */
    public function toArray(): array;
}
