# Changelog

All notable changes to `laravel-managed-jobs` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-07-22

### Changed (breaking)

- **Polymorphic job owner.** `managed_jobs.owner_user_id`, `owner_tenant_id` and
  `triggered_by_user_id` are replaced by `owner_type` + `owner_id` and
  `triggered_by_type` + `triggered_by_id`. Any Eloquent model can now own a job —
  the package no longer assumes a user/tenant shape.
- **`JobRunner::dispatch()`** accepts any `Illuminate\Database\Eloquent\Model` as
  `$owner` / `$triggeredBy` instead of a `JobOwner`. Existing calls that pass a
  model (`owner: $request->user()`) keep working unchanged.
- **Broadcast channel names** include the owner type by default:
  `jobs.{ownerType}.{ownerId}` (e.g. `jobs.user.5`) instead of `jobs.{id}`.
  Update Echo subscriptions and `routes/channels.php`, or set
  `broadcasting.include_owner_type` to `false` to keep flat `jobs.{id}` names
  (safe only with a single owner type).
- Config keys `user_model` and `user_primary_key` removed — the owner is resolved
  polymorphically. A published config that still contains them is unaffected
  (unknown keys are ignored).

### Added

- `JobChannelResolver` contract and `DefaultJobChannelResolver`: broadcast channel
  policy is now pluggable via `broadcasting.resolver`, so apps can broadcast to
  tenant / team / service channels without patching the package.
- `broadcasting.include_owner_type` config flag.
- `ManagedJob::owner()` / `ManagedJob::triggeredBy()` polymorphic `morphTo` relations.
- `ManagedJob::ownedBy($model)` query scope (replaces the `where('owner_user_id', …)` idiom).
- Testbench-based test suite for the channel resolver.
- Publishable v1 → v2 data migration (`--tag=managed-jobs-upgrades`).

### Fixed

- **Cross-owner broadcast channel collision.** In v1 a `user 7` job and a
  `tenant 7` job both broadcast on `jobs.7`, leaking lifecycle events across
  owners. The owner type is now part of the channel name (`jobs.user.7` vs
  `jobs.tenant.7`), so this no longer happens; register a morph map to guarantee
  a distinct, stable segment per owner type. (Supersedes PR #1.)

### Deprecated

- `JobOwner` is now an empty marker interface with no required methods, kept only
  so v1 `implements JobOwner` declarations keep compiling. Pass the owner model
  directly to `JobRunner::dispatch()`.

### Upgrading

See [UPGRADE.md](UPGRADE.md).

## [1.x]

- Initial releases: job lifecycle tracking, real-time progress broadcasting via
  Laravel Echo, file management with automatic expiry, and per-dispatch queue
  overrides.
