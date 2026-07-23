<?php

namespace YourVendor\ManagedJobs\Contracts;

/**
 * Optional marker interface for a model that can own a managed job.
 *
 * As of v2 the package identifies owners polymorphically (owner_type /
 * owner_id), so ANY Eloquent model can own a job. Implementing this interface
 * is therefore optional and carries no required methods — it exists only to let
 * you express intent, and so that v1 `implements JobOwner` declarations keep
 * compiling after upgrading.
 *
 * v1 required getManagedJobOwnerId() and getManagedJobTenantId(). Those are no
 * longer used by the package: pass the owner model itself to
 * JobRunner::dispatch() and the package reads $owner->getMorphClass() /
 * $owner->getKey(). You may keep the old methods on your model or remove them.
 *
 * @deprecated since 2.0 — no longer required; pass any Eloquent model as the owner.
 */
interface JobOwner
{
}
