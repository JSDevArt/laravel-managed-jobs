<?php

namespace YourVendor\ManagedJobs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use YourVendor\ManagedJobs\Models\ManagedJobFile;

class ExpireJobFilesCommand extends Command
{
    protected $signature = 'managed-jobs:expire-files';

    protected $description = 'Delete physical files that have expired (expires_at <= now) and soft-delete their managed_job_files records.';

    public function handle(): int
    {
        $expired = ManagedJobFile::query()
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expired as $file) {
            /** @var ManagedJobFile $file */
            $this->deletePhysicalFile($file->path);
            $file->delete();
            $count++;
        }

        if ($count > 0) {
            $this->info("Expired and soft-deleted {$count} job file(s).");
        }

        return self::SUCCESS;
    }

    private function deletePhysicalFile(string $path): void
    {
        $disk = config('managed-jobs.storage.disk');
        $storage = $disk !== null ? Storage::disk($disk) : Storage::disk();

        if ($storage->exists($path)) {
            $storage->delete($path);

            return;
        }

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
