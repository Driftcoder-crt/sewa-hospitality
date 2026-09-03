<?php

namespace App\Support\Audit;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Audit kernel (05-security-reliability.md §1.4 / §4): one row in
 * `activity_log` on every admin/portal mutation — who / what / when /
 * diff, with sensitive keys redacted BEFORE persistence (7-year audit
 * retention never stores live credentials).
 *
 * Hard rule: audit must never break a user flow. Any persistence failure
 * is reported to the ops channel and swallowed — the user's write already
 * succeeded inside its transaction; losing the audit row is an ops
 * problem, not a user-facing error.
 */
final class ActivityLogger
{
    /**
     * Keys redacted recursively (case-insensitive) to '[redacted]'.
     */
    public const REDACTED_KEYS = [
        'password', 'password_confirmation', 'token', 'secret',
        'two_factor_secret', 'two_factor_recovery_codes',
        'card', 'cvv',
    ];

    /**
     * Record an audit entry.
     *
     * @param  string  $context  admin|portal|api|system.
     * @param  string  $action  create|update|delete|login|export|publish|…
     * @param  Model|null  $subject  Affected model.
     * @param  array<string, mixed>  $changes  Diff payload (redacted on write).
     * @param  User|null  $user  Acting user; falls back to the
     *                           authenticated session user.
     */
    public static function log(string $context, string $action, ?Model $subject = null, array $changes = [], ?User $user = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => $user?->id ?? auth()->id(),
                'context' => $context,
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'changes' => self::redact($changes),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (Throwable $e) {
            // Audit must never break a user flow — surface for the ops
            // digest and move on.
            Log::channel('ops')->error('ActivityLogger could not persist audit entry', [
                'context' => $context,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recursively replace sensitive keys with '[redacted]'.
     *
     * @param  array<array-key, mixed>  $changes
     * @return array<array-key, mixed>
     */
    public static function redact(array $changes): array
    {
        $redacted = [];

        foreach ($changes as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            $redacted[$key] = is_array($value)
                ? self::redact($value)
                : $value;
        }

        return $redacted;
    }
}
