<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Nightly MySQL dump → gzip into config('sewa.backups_path')
 * (06-hosting-deployment.md §8: RPO 24h, verified by backups:verify).
 *
 * Security notes:
 *   • The MySQL password is passed via the MYSQL_PWD process env var,
 *     never on the command line — `ps` on a shared host must never
 *     reveal credentials, and the command string is never logged.
 *   • Output is verified (exists AND > 100 bytes) before success is
 *     reported; a silent 12-byte "dump" must not count as a backup.
 *   • Retention prunes *.sql.gz older than --retention days (mtime).
 */
final class DbBackup extends Command
{
    protected $signature = 'db:backup {--retention=7}';

    protected $description = 'Dump the MySQL database (gzipped) to the backups path and prune old dumps';

    public function handle(): int
    {
        if ((string) config('database.default') !== 'mysql') {
            // Dev/sqlite: nothing dumpable here — not an incident, but the
            // cron must NOT report success (backups:verify would rightly
            // complain on a real host).
            $message = 'db:backup skipped — connection "'.config('database.default').'" is not mysql (no dump for sqlite dev).';

            $this->components->info($message);
            Log::channel('ops')->info($message);

            return self::FAILURE;
        }

        $connection = (array) config('database.connections.mysql');
        $dir = (string) config('sewa.backups_path');

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            Log::channel('ops')->error('db:backup cannot create backups directory', ['path' => $dir]);
            $this->error('db:backup cannot create backups directory: '.$dir);

            return self::FAILURE;
        }

        $file = $dir.'/sewa-db-'.now()->format('Ymd-His').'.sql.gz';

        $dump = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --routines --triggers --no-tablespaces %s | gzip > %s',
            escapeshellarg((string) $connection['host']),
            escapeshellarg((string) $connection['port']),
            escapeshellarg((string) $connection['username']),
            escapeshellarg((string) $connection['database']),
            escapeshellarg($file),
        );

        // Credentials travel in the process env (MYSQL_PWD), never in the
        // shell string; Symfony Process merges this over the inherited env.
        $process = Process::fromShellCommandline($dump, null, [
            'MYSQL_PWD' => (string) $connection['password'],
        ], null, 900.0);

        try {
            $process->run();
        } catch (Throwable $e) {
            Log::channel('ops')->error('db:backup failed to start mysqldump', [
                'error' => $e->getMessage(),
                'file' => $file,
            ]);
            $this->error('db:backup failed — see ops log.');

            return self::FAILURE;
        }

        if (! $process->isSuccessful()) {
            Log::channel('ops')->error('db:backup dump failed', [
                'exit_code' => $process->getExitCode(),
                'stderr' => mb_substr($process->getErrorOutput(), 0, 500),
                'file' => $file,
            ]);
            $this->error('db:backup dump failed — see ops log.');

            return self::FAILURE;
        }

        clearstatcache();

        if (! is_file($file) || filesize($file) <= 100) {
            @unlink($file);
            Log::channel('ops')->error('db:backup output missing or suspiciously small — discarded', ['file' => $file]);
            $this->error('db:backup produced no usable dump — see ops log.');

            return self::FAILURE;
        }

        $pruned = $this->pruneOldBackups($dir, max(0, (int) $this->option('retention')));

        $sizeBytes = (int) filesize($file);
        Log::channel('ops')->info('db:backup completed', [
            'file' => $file,
            'size_bytes' => $sizeBytes,
            'pruned' => $pruned,
        ]);
        $this->components->info(sprintf(
            'Backup written: %s (%s)%s',
            $file,
            number_format($sizeBytes / 1024, 1).' KB',
            $pruned > 0 ? sprintf(' — pruned %d old dump(s)', $pruned) : '',
        ));

        return self::SUCCESS;
    }

    /**
     * Delete *.sql.gz dumps older than $days (by filesystem mtime).
     *
     * @return int Number of deleted files.
     */
    private function pruneOldBackups(string $dir, int $days): int
    {
        if ($days === 0) {
            return 0;
        }

        $cutoff = time() - ($days * 86400);
        $pruned = 0;

        foreach (glob($dir.'/*.sql.gz') ?: [] as $old) {
            if (is_file($old) && filemtime($old) !== false && filemtime($old) < $cutoff) {
                if (@unlink($old)) {
                    $pruned++;
                }
            }
        }

        return $pruned;
    }
}
