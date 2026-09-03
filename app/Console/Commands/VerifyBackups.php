<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Daily backup verification (06-hosting-deployment.md §8: "latest file
 * < 26h old, size sane"; 12-monitoring.md alert matrix: a verification
 * failure is SEV-1 — data — and pages ops immediately via the ops log).
 *
 * The honest three checks, in order:
 *   1. A sewa-db-*.sql.gz exists at all,
 *   2. the newest one is younger than 26h (nightly cadence + slack),
 *   3. it is big enough to plausibly contain the schema + data.
 */
final class VerifyBackups extends Command
{
    protected $signature = 'backups:verify';

    protected $description = 'Verify the latest database backup exists, is fresh (<26h old) and size-sane';

    /** Youngest a nightly backup may be before verification fails. */
    private const MAX_AGE_HOURS = 26;

    /** Bytes below which a "dump" is considered broken/truncated. */
    private const MIN_SIZE_BYTES = 10_000;

    public function handle(): int
    {
        $dir = (string) config('sewa.backups_path');

        $backups = Collection::make(glob($dir.'/sewa-db-*.sql.gz') ?: [])
            ->filter(is_file(...))
            ->sortByDesc(fn (string $path): int => (int) filemtime($path))
            ->values();

        if ($backups->isEmpty()) {
            return $this->reportFailure('no backup found', ['path' => $dir]);
        }

        $latest = (string) $backups->first();
        $ageHours = (time() - (int) filemtime($latest)) / 3600;
        $sizeBytes = (int) filesize($latest);

        if ($ageHours > self::MAX_AGE_HOURS) {
            return $this->reportFailure('latest backup is stale', [
                'file' => $latest,
                'age_hours' => round($ageHours, 1),
            ]);
        }

        if ($sizeBytes < self::MIN_SIZE_BYTES) {
            return $this->reportFailure('latest backup is suspiciously small', [
                'file' => $latest,
                'size_bytes' => $sizeBytes,
            ]);
        }

        $this->components->info(sprintf(
            'Backup OK: %s (%s, %.1fh old, %d dump(s) on disk).',
            basename($latest),
            number_format($sizeBytes / 1024, 1).' KB',
            $ageHours,
            $backups->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * Report a verification failure: ops log first (SEV-1 per the
     * monitoring alert matrix), then the console line for the cron log.
     */
    private function reportFailure(string $reason, array $context = []): int
    {
        Log::channel('ops')->error('Backup verification FAILED', [...$context, 'reason' => $reason]);
        $this->error('Backup verification FAILED: '.$reason);

        return self::FAILURE;
    }
}
