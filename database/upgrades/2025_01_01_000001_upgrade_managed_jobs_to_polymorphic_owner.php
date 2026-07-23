<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade managed_jobs from v1 to the v2 polymorphic owner.
 *
 *   v1: owner_user_id, owner_tenant_id, triggered_by_user_id
 *   v2: owner_type + owner_id, triggered_by_type + triggered_by_id
 *
 * This migration is published on purpose (it is NOT loaded automatically) so
 * you can review it before it rewrites existing rows. Two things to confirm:
 *
 *   1. $ownerModel below — the model that owned every existing job in v1.
 *      Backfilled rows get its morph value, which MUST match what JobRunner
 *      writes for new jobs ($owner->getMorphClass()). Defaults to
 *      App\Models\User; edit it if your v1 owner was a different model. (v2 no
 *      longer keeps a user_model config key, so this is a plain, explicit value.)
 *
 *   2. owner_tenant_id — v2 has no tenant column. A tenant channel is now a
 *      custom JobChannelResolver concern. If you still need the tenant id,
 *      comment out the drop below and read the column from your resolver (or
 *      copy it into your own table first).
 *
 * Publish with:  php artisan vendor:publish --tag=managed-jobs-upgrades
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('managed-jobs.table_prefix', '') . 'managed_jobs';

        // The model that owned every existing job in v1. Its morph value is
        // written to the backfilled rows and MUST equal what JobRunner stores
        // for new jobs ($owner->getMorphClass()). EDIT if it was not User.
        $ownerModel = \App\Models\User::class;
        $ownerType  = (new $ownerModel)->getMorphClass();

        // New columns are added nullable and left nullable on upgraded tables
        // (the fresh install schema is NOT NULL). Tightening them would fail for
        // any legacy row that had a null owner, so we keep them permissive here.
        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'owner_type')) {
                $t->string('owner_type')->nullable()->after('progress_message');
            }
            if (! Schema::hasColumn($table, 'owner_id')) {
                $t->string('owner_id')->nullable()->after('owner_type');
            }
            if (! Schema::hasColumn($table, 'triggered_by_type')) {
                $t->string('triggered_by_type')->nullable()->after('owner_id');
            }
            if (! Schema::hasColumn($table, 'triggered_by_id')) {
                $t->string('triggered_by_id')->nullable()->after('triggered_by_type');
            }
        });

        if (Schema::hasColumn($table, 'owner_user_id')) {
            DB::table($table)
                ->whereNotNull('owner_user_id')
                ->update([
                    'owner_type' => $ownerType,
                    'owner_id'   => DB::raw('owner_user_id'),
                ]);
        }

        if (Schema::hasColumn($table, 'triggered_by_user_id')) {
            DB::table($table)
                ->whereNotNull('triggered_by_user_id')
                ->update([
                    'triggered_by_type' => $ownerType,
                    'triggered_by_id'   => DB::raw('triggered_by_user_id'),
                ]);
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            $t->index(['owner_type', 'owner_id']);
            $t->index(['triggered_by_type', 'triggered_by_id']);

            // v2 has no dedicated tenant column. Remove this block to keep it.
            if (Schema::hasColumn($table, 'owner_tenant_id')) {
                $t->dropColumn('owner_tenant_id');
            }
            if (Schema::hasColumn($table, 'owner_user_id')) {
                $t->dropColumn('owner_user_id');
            }
            if (Schema::hasColumn($table, 'triggered_by_user_id')) {
                $t->dropColumn('triggered_by_user_id');
            }
        });
    }

    public function down(): void
    {
        // Best-effort reversal only. It restores the user-id columns from
        // owner_id, but data dropped in up() (owner_tenant_id, and the owner
        // *type* of any non-user owner) cannot be recovered, and a non-integer
        // owner_id (ULID/UUID) will not fit the recreated unsignedBigInteger.
        // Intended for rolling back a fresh, user-only upgrade — not a
        // general-purpose down migration.
        $table = config('managed-jobs.table_prefix', '') . 'managed_jobs';

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'owner_user_id')) {
                $t->unsignedBigInteger('owner_user_id')->nullable()->after('progress_message');
            }
            if (! Schema::hasColumn($table, 'owner_tenant_id')) {
                $t->unsignedBigInteger('owner_tenant_id')->nullable()->after('owner_user_id');
            }
            if (! Schema::hasColumn($table, 'triggered_by_user_id')) {
                $t->unsignedBigInteger('triggered_by_user_id')->nullable()->after('owner_tenant_id');
            }
        });

        if (Schema::hasColumn($table, 'owner_id')) {
            DB::table($table)->update(['owner_user_id' => DB::raw('owner_id')]);
        }
        if (Schema::hasColumn($table, 'triggered_by_id')) {
            DB::table($table)
                ->whereNotNull('triggered_by_id')
                ->update(['triggered_by_user_id' => DB::raw('triggered_by_id')]);
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            foreach (['owner_type', 'owner_id', 'triggered_by_type', 'triggered_by_id'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
