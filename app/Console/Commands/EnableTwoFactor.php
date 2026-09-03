<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\RecoveryCode;
use Throwable;

/**
 * Bootstrap TOTP enrolment for a staff account (03-technical-specs/
 * 05-security-reliability.md §1.1 — 2FA is mandatory for super-admin/admin).
 * The full enrolment UI ships with the System screens (M1); until then this
 * command is the supported path, reachable from admin → Security (2FA).
 *
 * Flow: generate a shared secret when absent → verify a one-time code from
 * the operator's authenticator → mark the device confirmed → print the
 * secret, the otpauth URI and 8 single-use recovery codes exactly once.
 */
class EnableTwoFactor extends Command
{
    protected $signature = 'user:enable-2fa {email} {--code=} {--force}';

    protected $description = 'Enable and confirm TOTP two-factor authentication for a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found for [{$email}].");

            return self::FAILURE;
        }

        if ($user->two_factor_confirmed_at !== null && ! $this->option('force')) {
            $this->info("{$email} already confirmed two-factor authentication on "
                .$user->two_factor_confirmed_at->format('Y-m-d H:i:s').'.');
            $this->line('Re-run with --force to rotate the secret and recovery codes.');

            return self::SUCCESS;
        }

        if ($user->two_factor_secret === null || $this->option('force')) {
            $user->generateTwoFactorSecret();
            $user->save();
        }

        $code = (string) ($this->option('code') ?: prompt('One-time code from your authenticator'));

        try {
            $confirmed = $user->confirmTwoFactorAuthentication($code);
        } catch (Throwable) {
            // Some Fortify releases signal an invalid code by throwing
            // instead of returning false — treat both as a failed verify.
            $confirmed = false;
        }

        if (! $confirmed) {
            $this->error('The one-time code is invalid. Nothing was confirmed — re-run the command with a fresh code.');

            return self::FAILURE;
        }

        // Exactly 8 single-use codes, persisted in Fortify's canonical
        // storage shape (encrypted JSON) so any Fortify release can read
        // them back regardless of model cast configuration.
        $recoveryCodes = Collection::times(8, fn (): string => RecoveryCode::generate())->all();

        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));

        // The users table additionally carries two_factor_enabled for fast
        // System-screen filtering; Fortify's own enrolment marker remains
        // two_factor_confirmed_at (what the admin.2fa middleware checks).
        if (Schema::hasColumn($user->getTable(), 'two_factor_enabled')) {
            $user->two_factor_enabled = true;
        }

        $user->save();

        $this->info("Two-factor authentication is now confirmed for {$email}.");
        $this->line('Authenticator secret: '.$this->plainSecret($user));
        $this->line('otpauth URI:          '.$user->twoFactorQrCodeUri());

        $this->table(
            ['Recovery code (single use)'],
            array_map(fn (string $code): array => [$code], $recoveryCodes),
        );

        $this->warn('Store the secret and recovery codes in your password manager now — they are shown once and never again.');

        return self::SUCCESS;
    }

    /**
     * The secret is stored encrypted (Fortify writes encrypt(plain)); the
     * otpauth URI is built from the decrypted value, so parse the secret
     * back out of the URI rather than assuming a model cast.
     */
    protected function plainSecret(User $user): string
    {
        $query = parse_url($user->twoFactorQrCodeUri(), PHP_URL_QUERY) ?: '';
        parse_str($query, $params);

        return (string) ($params['secret'] ?? decrypt($user->two_factor_secret));
    }
}
