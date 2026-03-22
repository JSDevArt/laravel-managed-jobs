<?php

namespace YourVendor\ManagedJobs\Contracts;

/**
 * Contract that the application's User model must implement.
 *
 * This allows the package to remain agnostic of your User model's
 * column names and tenant structure.
 *
 * Minimal implementation example:
 *
 *   class User extends Authenticatable implements JobOwner
 *   {
 *       public function getManagedJobOwnerId(): int|string
 *       {
 *           return $this->id; // or $this->user_id, etc.
 *       }
 *
 *       public function getManagedJobTenantId(): int|string|null
 *       {
 *           return null; // or $this->tenant_id for multi-tenant apps
 *       }
 *   }
 */
interface JobOwner
{
    /**
     * The identifier stored in managed_jobs.owner_user_id.
     * Must match what the controller uses to scope job queries.
     */
    public function getManagedJobOwnerId(): int|string;

    /**
     * Optional tenant identifier stored in managed_jobs.owner_tenant_id.
     * Used to broadcast events to a tenant-wide channel.
     * Return null if your app is not multi-tenant.
     */
    public function getManagedJobTenantId(): int|string|null;
}
