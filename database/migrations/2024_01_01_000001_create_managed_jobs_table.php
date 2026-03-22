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
            $table->unsignedBigInteger('owner_user_id');
            $table->unsignedBigInteger('owner_tenant_id')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
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
