<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_job_files', function (Blueprint $table) {
            $table->id('job_file_id');
            $table->unsignedBigInteger('job_id');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_id')
                ->references('job_id')
                ->on('managed_jobs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_job_files');
    }
};
