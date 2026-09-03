<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Bootstrap super-admin provisioning: `php artisan sewa:admin ops@…`.
 * M0 tooling for the first-deploy runbook — the System screens (M1) take
 * over day-to-day user management afterwards.
 *
 * Security notes (03-technical-specs/05-security-reliability.md §1.1):
 * - the generated password is printed to the terminal EXACTLY ONCE and is
 *   never written to logs or storage;
 * - existing accounts keep their password (rotation belongs to the
 *   password-reset flow, never to a bootstrap command);
 * - production requires --force, so a super-admin is never created by
 *   accident on a live host.
 */
class MakeAdmin extends Command
{
    protected $signature = 'sewa:admin {email} {--name=} {--force}';

    protected $description = 'Create or promote a super-admin account';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to create a super-admin in production without --force.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("[{$email}] is not a valid email address.");

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: Str::ucfirst(Str::before($email, '@')));

        $user = User::query()->where('email', $email)->first();
        $generatedPassword = null;

        if ($user === null) {
            $generatedPassword = Str::password(16);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $generatedPassword, // hashed by the model cast
                'locale' => 'en',
                'status' => UserStatus::Active,
            ]);
        } else {
            if ($this->option('name') !== null) {
                $user->name = $name;
            }

            $user->status = UserStatus::Active;
            $user->save();
        }

        $this->ensureSuperAdminRole();
        $user->assignRole('super-admin');

        $this->info("Super-admin ready: {$user->email} ({$name})");

        if ($generatedPassword !== null) {
            $this->warn('Password — shown exactly once, never logged again:');
            $this->line($generatedPassword);
        } else {
            $this->line('Existing account — password left unchanged.');
        }

        return self::SUCCESS;
    }

    /**
     * The role normally comes from RolesAndPermissionsSeeder; create the
     * minimum here so the command also works on a bare database.
     */
    protected function ensureSuperAdminRole(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['slug' => 'super-admin', 'display_name' => 'Super Admin'],
        );
    }
}
