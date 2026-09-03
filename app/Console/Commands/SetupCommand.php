<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * sewa:setup — non-destructive readiness report for a fresh environment
 * (runbook first-run gate). It READS the environment, prints an honest
 * check table, and finishes with the manual go-live checklist. It never
 * migrates, seeds, or overwrites anything: publishing vendor migration
 * stubs is safe/idempotent and is the only write-ish action attempted.
 */
final class SetupCommand extends Command
{
    protected $signature = 'sewa:setup';

    protected $description = 'Report environment readiness for SEWA HOSPITALITY and print the go-live checklist (non-destructive)';

    /** Extensions the platform genuinely requires on Hostinger PHP 8.3. */
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql', 'gd', 'exif', 'mbstring', 'openssl', 'fileinfo',
    ];

    public function handle(): int
    {
        $rows = [];
        $hardFailures = 0;

        // PHP ^8.3 is pinned in composer.json (Hostinger runs 8.3 FPM).
        $phpOk = version_compare(PHP_VERSION, '8.3.0', '>=');
        $rows[] = ['PHP >= 8.3', $phpOk ? 'ok — '.PHP_VERSION : 'FAIL — '.PHP_VERSION];
        $hardFailures += $phpOk ? 0 : 1;

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $rows[] = ["ext-{$extension}", $loaded ? 'ok' : 'FAIL — missing'];
            $hardFailures += $loaded ? 0 : 1;
        }

        $envExists = is_file(base_path('.env'));
        $rows[] = ['.env present', $envExists ? 'ok' : 'missing — copy from .env.example'];

        $appKeySet = (bool) config('app.key');
        $rows[] = ['APP_KEY set', $appKeySet ? 'ok' : 'missing — run: php artisan key:generate'];

        $rows[] = ['storage:link', $this->ensureStorageLink()];

        $rows[] = ['Pulse migrations published', $this->publishPulseMigrations()];
        // NOTE: spatie/laravel-medialibrary migrations are NEVER published —
        // we own create_media_table (database/migrations/0001_01_01_000000)
        // with ULIDs + our media namespaces (09-media-pipeline.md §2).

        $this->components->info('SEWA HOSPITALITY — environment report');
        $this->table(['Check', 'Status'], $rows);

        $this->newLine();
        $this->components->info('Go-live checklist');
        $this->components->bulletList([
            'composer run seed:prod-minimal',
            'php artisan sewa:admin you@sewahospitality.com',
            'set TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY (+ TURNSTILE_FAIL_MODE)',
            'composer run seed:demo   (staging only)',
        ]);

        if ($hardFailures > 0) {
            $this->components->error("{$hardFailures} hard requirement(s) unmet — fix them before deploying.");

            return self::FAILURE;
        }

        $this->components->info('Hard requirements all met. Soft items (env/app key/links) are listed above.');

        return self::SUCCESS;
    }

    /**
     * Create the public/storage symlink when absent (idempotent,
     * safe to re-run per the deploy runbook).
     */
    private function ensureStorageLink(): string
    {
        if (is_link(public_path('storage')) || is_dir(public_path('storage'))) {
            return 'ok — present';
        }

        try {
            Artisan::call('storage:link');

            return is_link(public_path('storage')) ? 'created' : 'failed — check storage/app/public';
        } catch (Throwable $e) {
            return 'error — '.$e->getMessage();
        }
    }

    /**
     * Best-effort publish of the Pulse migration stubs. When the package
     * is not installed (yet), this is reported honestly and skipped —
     * Pulse wiring belongs to the monitoring milestone.
     */
    private function publishPulseMigrations(): string
    {
        try {
            Artisan::call('vendor:publish', ['--tag' => 'pulse-migrations']);

            return 'done';
        } catch (Throwable) {
            return 'skipped — pulse package/tag not present';
        }
    }
}
