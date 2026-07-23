# Upgrade guide

## v1 → v2

v2 makes job ownership **polymorphic**: a job can be owned by any Eloquent model,
not just your `User`. This drops the built-in user/tenant assumption and fixes the
cross-owner broadcast channel collision (a `user 7` job and a `tenant 7` job no
longer share `jobs.7`).

The steps are ordered. Most apps finish in a few minutes; the only unavoidable
work is updating your frontend channel names (step 6).

### 1. Bump the package

```bash
composer require jsdevart/laravel-managed-jobs:^2.0
```

### 2. Migrate the database

**New installs:** nothing special — `php artisan migrate` creates the polymorphic
schema directly.

**Existing installs:** publish the data-preserving upgrade migration, review it,
then migrate. It backfills `owner_type` / `owner_id` from `owner_user_id` and
drops the old columns.

```bash
php artisan vendor:publish --tag=managed-jobs-upgrades
# review the migration — set $ownerModel at the top to your v1 owner model
# (defaults to App\Models\User) and confirm the owner_tenant_id handling
# (see step 7) — then:
php artisan migrate
```

### 3. Dispatch calls — usually no change

If you already pass a model, nothing changes:

```php
JobRunner::dispatch(
    job:     GenerateReportJob::class,
    payload: $payload,
    owner:   $request->user(),   // any Eloquent model
);
```

`$triggeredBy` is now any model too (was a `JobOwner`).

### 4. The `JobOwner` interface is now optional

`getManagedJobOwnerId()` / `getManagedJobTenantId()` are no longer used. You may:

- leave `implements JobOwner` and the methods on your model (harmless), or
- remove them entirely.

Nothing in the package calls them anymore.

### 5. Queries

Replace the v1 idiom:

```php
// before
ManagedJob::where('owner_user_id', $request->user()->getManagedJobOwnerId())->get();

// after
ManagedJob::ownedBy($request->user())->get();
// or explicitly:
ManagedJob::where('owner_type', $user->getMorphClass())
    ->where('owner_id', $user->getKey())
    ->get();
```

### 6. Broadcast channels (frontend + authorization)

Channel names changed from `jobs.{id}` to `jobs.{ownerType}.{ownerId}`
(e.g. `jobs.user.5`).

```js
// Laravel Echo
Echo.channel(`jobs.user.${userId}`)  // was `jobs.${userId}`
```

```php
// routes/channels.php
Broadcast::channel('jobs.user.{userId}', fn ($user, $userId) =>
    (int) $user->id === (int) $userId);
```

Two ways to reduce this work:

- **Keep short, stable aliases** — register a morph map so the type segment stays
  `user` rather than `app_models_user`:

  ```php
  Relation::enforceMorphMap(['user' => \App\Models\User::class]);
  ```

- **Zero frontend change** (only if every job is owned by a single owner type) —
  keep the flat v1 name:

  ```php
  'broadcasting' => ['include_owner_type' => false], // → jobs.{id}
  ```

### 7. Tenant channel (only if you used `owner_tenant_id`)

v2 has no tenant column. A tenant channel is now a **custom resolver** concern.
Before migrating:

1. In the published upgrade migration, comment out the `dropColumn('owner_tenant_id')`
   line if you want to keep the value, **or** copy it into your own table first.
2. Implement a `JobChannelResolver` that also emits `jobs.tenant.{id}` and point
   `config('managed-jobs.broadcasting.resolver')` at it. See **Custom channel
   policy** in the [README](README.md#custom-channel-policy).

### 8. Config

- Remove `user_model` and `user_primary_key` from your published config (harmless
  if left — they are ignored).
- New broadcasting keys: `resolver`, `include_owner_type`. If your published
  config predates them, the package falls back to safe defaults, so republishing
  is optional.
