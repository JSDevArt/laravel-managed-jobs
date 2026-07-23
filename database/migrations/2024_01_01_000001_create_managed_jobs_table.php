<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_jobs', function (Blueprint $table) {
            $table->id('job_id');
            $table->string('type');
            $table->string('status');
            $table->json('payload');
            $table->json('state')->nullable();
            $table->tinyInteger('progress_percentage')->default(0);
            $table->string('progress_message')->nullable();

            // Polymorphic owner — any Eloquent model (user, tenant, service…).
            // owner_id is a string so both auto-increment and ULID/UUID keys fit.
            $table->string('owner_type');
            $table->string('owner_id');
            $table->index(['owner_type', 'owner_id']);

            // Optional model that triggered the job (e.g. an admin acting for the owner).
            $table->string('triggered_by_type')->nullable();
            $table->string('triggered_by_id')->nullable();
            $table->index(['triggered_by_type', 'triggered_by_id']);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_jobs');
    }
};
