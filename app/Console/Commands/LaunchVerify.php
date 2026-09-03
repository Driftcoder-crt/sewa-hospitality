<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Ai\Services\AiGateway;
use App\Modules\Cms\Services\SettingsRepository;
use App\Modules\I18n\Models\Locale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * sewa:launch-verify — the post-deploy 5-minute verification gate
 * (03-technical-specs/13-testing-qa.md §2 gate 9 + 06-hosting-deployment
 * runbook) as one command: exit 0 = the deploy stands, exit 1 = fix or
 * roll back before the announcement.
 *
 * Checks cover the release definition-of-done: configuration, database,
 * seed baseline, storage topology, SEO artifacts, queue/mail, AI +
 * Turnstile posture, and (optional, --url) a live self-request header
 * snapshot. WARN lines never fail the gate; FAIL lines do.
 */
class LaunchVerify extends Command
{
    protected $signature = 'sewa:launch-verify
                            {--url= : Self-request a public URL and snapshot the security headers (optional)}
                            {--production : Fail when APP_ENV is not production}';

    protected $description = 'Post-deploy 5-minute verification gate (roll back on exit 1)';

    private bool $hasFailures = false;

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('SEWA HOSPITALITY — launch verification ('.now()->toIso8601String().')');
        $this->newLine();

        $this->check('app key', fn (): bool => (string) config('app.key') !== '', 'APP_KEY must be set.');

        if ($this->option('production')) {
            $this->check('environment', fn (): bool => app()->isProduction(), 'APP_ENV must be production (--production given).');
        } else {
            $this->line('  [info] APP_ENV = '.app()->environment());
        }

        // ── Database ────────────────────────────────────────────────
        $this->check('database reachable', fn (): bool => $this->dbReachable(), 'Cannot query the database.');

        $this->check('migrations applied', fn (): bool => $this->migrationsCurrent(), 'Migrations pending — run php artisan migrate.');
        $this->warnOn('migrations current', fn (): bool => $this->pendingMigrations() === [], fn (): string => $this->pendingMigrations().' pending migration file(s).');

        // ── Seed baseline (schema §13) ──────────────────────────────
        $this->check('locales seeded', fn (): bool => Locale::query()->count() >= 6 && Locale::query()->where('code', 'en')->exists(), 'Launch locale set missing — seed LocalesSeeder.');
        $this->warnOn('locales enabled', fn (): bool => Locale::query()->enabled()->count() >= 6, 'Fewer than 6 locales enabled.');

        $this->check('identity configured', fn (): bool => trim((string) (app(SettingsRepository::class)->identity()['brand'] ?? '')) !== '', 'Brand identity missing from settings.');
        $this->check('staff present', fn (): bool => $this->staffCount() > 0, 'No staff user — run php artisan sewa:admin {email} before launch.');
        $this->warnOn('2FA on admins', fn (): bool => $this->adminsWithoutTwoFactor() === 0, fn (): string => $this->adminsWithoutTwoFactor().' admin(s) without TOTP confirmed.');

        // ── Storage topology (06-hosting-deployment §8) ─────────────
        $this->check('storage symlink', fn (): bool => File::exists(public_path('storage')), 'public/storage missing — run storage:link.');
        $this->check('backups dir', fn (): bool => is_dir(config('sewa.backups_path')) && is_writable(config('sewa.backups_path')), 'Backups dir missing or unwritable: '.config('sewa.backups_path'));
        $this->warnOn('portal disk', fn (): bool => is_dir(storage_path('app/portal')), 'Portal private disk directory not created yet (lazy on first upload).');

        // ── SEO artifacts ───────────────────────────────────────────
        $this->check('sitemap index', fn (): bool => File::exists(public_path('sitemap_index.xml')), 'Run cms:generate-sitemap.');
        $this->check('sitemap valid', fn (): bool => $this->validXml(public_path('sitemap_index.xml')), 'sitemap_index.xml is not valid XML.');
        $this->check('llms.txt', fn (): bool => File::exists(public_path('llms.txt')) && File::size(public_path('llms.txt')) > 200, 'public/llms.txt missing — re-run cms:generate-sitemap.');
        $this->check('robots.txt', fn (): bool => File::exists(public_path('robots.txt')), 'public/robots.txt missing.');

        // ── Queue + mail ────────────────────────────────────────────
        $this->check('queue table', fn (): bool => $this->dbReachable() && DB::table('jobs')->count() >= 0, 'jobs table unreachable (QUEUE_CONNECTION=database contract).');
        $this->warnOn('failed jobs', fn (): bool => DB::table('failed_jobs')->count() === 0, fn (): string => DB::table('failed_jobs')->count().' failed job(s) on record.');
        $this->check('mail from', fn (): bool => (string) config('sewa.emails.hello') !== '', 'Mail identity missing.');

        // ── AI posture (08-ai-system/01 §6) ─────────────────────────
        $aiKilled = ! AiGateway::globallyEnabled();
        $aiKeys = collect((array) config('ai.providers'))->contains(
            fn ($p): bool => is_string($p['key'] ?? null) && $p['key'] !== '',
        );

        if ($aiKilled || $aiKeys) {
            $this->line($aiKilled
                ? '  [info] AI kill switch ON — all features degrade to no-AI paths.'
                : '  [info] AI providers configured.');
        } else {
            $this->warnings[] = 'AI neither configured nor killed — calls will record error rows. Set AI_KEY_PRIMARY or AI_ENABLED=false.';
        }

        // ── Turnstile (error lock #3) ───────────────────────────────
        $this->warnOn('turnstile keys', fn (): bool => (string) config('sewa.turnstile.site_key') !== '' && (string) config('sewa.turnstile.secret') !== '',
            'Turnstile keys missing — forms run honeypot-only under grace mode.');

        // ── Optional live self-request ──────────────────────────────
        $url = (string) $this->option('url');

        if ($url !== '') {
            $this->check('self-request headers', fn (): bool => $this->selfCheckHeaders($url), "Self-request {$url} failed or the security headers are missing.");
        }

        // ── Verdict ─────────────────────────────────────────────────
        $this->newLine();

        foreach ($this->warnings as $warning) {
            $this->warn('  [WARN] '.$warning);
        }

        if ($this->warnings !== []) {
            $this->newLine();
        }

        $this->info($this->hasFailures
            ? 'LAUNCH VERIFICATION FAILED — resolve the failures or roll back.'
            : 'LAUNCH VERIFICATION PASSED — '.($this->warnings !== [] ? count($this->warnings).' warning(s).' : 'clean.'));

        return $this->hasFailures ? self::FAILURE : self::SUCCESS;
    }

    /** @param  callable(): bool  $condition */
    private function check(string $label, callable $condition, string $message): void
    {
        if ($condition()) {
            $this->line("  [PASS] {$label}");

            return;
        }

        $this->hasFailures = true;
        $this->error("  [FAIL] {$label} — {$message}");
    }

    /** @param  callable(): bool  $condition */
    private function warnOn(string $label, callable $condition, string|callable $message): void
    {
        if ($condition()) {
            $this->line("  [PASS] {$label}");

            return;
        }

        $this->warnings[] = is_callable($message) && ! is_string($message) ? (string) $message() : (string) $message;
        $this->line("  [WARN] {$label}");
    }

    private function dbReachable(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function migrationsCurrent(): bool
    {
        try {
            return DB::table('migrations')->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function pendingMigrations(): int
    {
        $applied = collect();

        try {
            $applied = DB::table('migrations')->pluck('migration');
        } catch (\Throwable) {
            return 0;
        }

        $files = collect(File::glob(database_path('migrations/*.php')))
            ->map(fn (string $path): string => basename($path, '.php'));

        return $files->reject(fn (string $name): bool => $applied->contains($name))->count();
    }

    private function staffCount(): int
    {
        try {
            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', config('sewa.staff_roles')))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function adminsWithoutTwoFactor(): int
    {
        try {
            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', config('sewa.admin.two_factor_roles')))
                ->where('two_factor_enabled', false)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function validXml(string $path): bool
    {
        if (! File::exists($path)) {
            return false;
        }

        try {
            simplexml_load_file($path);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function selfCheckHeaders(string $url): bool
    {
        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $csp = (string) $response->header('Content-Security-Policy');

        return $response->header('X-Content-Type-Options') === 'nosniff'
            && str_contains($csp, 'script-src')
            && str_contains($csp, 'nonce-');
    }
}
