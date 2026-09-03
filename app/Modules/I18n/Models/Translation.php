<?php

namespace App\Modules\I18n\Models;

use App\Models\User;
use App\Modules\I18n\Enums\TranslationStatus;
use App\Modules\I18n\Services\UiStrings;
use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UI string (03-technical-specs/03-database-schema.md §10 "translations").
 * Key-value row per locale+namespace+key; unique triple enforced in the
 * migration. Machine drafts never serve public surfaces unless the
 * per-namespace auto-publish policy allows (11-multilingual §4) — the
 * resolve() helper encodes that gate.
 *
 * The table is written by: machine seeding (UI-string import),
 * TranslateContent (feature strings ride the same review queue) and the
 * Translation Review Queue (admin) — every human approval stamps
 * reviewed_by with reviewer attribution (11-multilingual §6.2).
 *
 * Only updated_at exists by spec — the model therefore sets
 * CREATED_AT = null and touches updated_at manually.
 */
class Translation extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return TranslationFactory::new();
    }

    public const CREATED_AT = null;

    protected $fillable = [
        'locale', 'namespace', 'key', 'value', 'machine_value', 'status', 'reviewed_by', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'updated_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    /** @return BelongsTo<Locale, $this> */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale', 'code');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeInNamespace(Builder $query, string|UiNamespace $namespace): Builder
    {
        return $query->where('namespace', $namespace instanceof UiNamespace ? $namespace->value : $namespace);
    }

    public function scopeMachine(Builder $query): Builder
    {
        return $query->where('status', TranslationStatus::Machine->value);
    }

    public function scopeHumanReviewed(Builder $query): Builder
    {
        return $query->where('status', TranslationStatus::HumanReviewed->value);
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    public function isMachine(): bool
    {
        return $this->status === TranslationStatus::Machine;
    }

    /** Side-by-side review accept path: approve as-is with attribution. */
    public function approve(User $reviewer): void
    {
        $this->forceFill([
            'status' => TranslationStatus::HumanReviewed,
            'reviewed_by' => $reviewer->id,
            'updated_at' => now(),
        ])->save();
        UiStrings::bumpVersion();
    }

    /** Edit-and-approve path (11-multilingual §6.2: edit fields, approve). */
    public function approveWith(User $reviewer, string $value): void
    {
        // Preserve the machine draft the reviewer edits AWAY from — a
        // later reject() restores it (once per row, at first human
        // touch; a machine_value never gets clobbered).
        if ($this->status === TranslationStatus::Machine && $this->machine_value === null) {
            $this->forceFill(['machine_value' => $this->value]);
        }

        $this->forceFill([
            'value' => $value,
            'status' => TranslationStatus::HumanReviewed,
            'reviewed_by' => $reviewer->id,
            'updated_at' => now(),
        ])->save();
        UiStrings::bumpVersion();
    }

    /** Reject-with-note returns the row to machine state for re-drafting. */
    public function reject(User $reviewer): void
    {
        $this->forceFill([
            'status' => TranslationStatus::Machine,
            'reviewed_by' => $reviewer->id,
            'updated_at' => now(),
        ]);

        // The rejected human edit must not survive masquerading as the
        // machine draft — restore the preserved value where we have one.
        if ($this->machine_value !== null) {
            $this->forceFill(['value' => $this->machine_value]);
        }

        $this->save();
        UiStrings::bumpVersion();
    }

    /**
     * Idempotent upsert used by the seeding/import path — one row per
     * locale+namespace+key, never duplicates. New rows land as machine
     * drafts; re-imports do NOT clobber human-approved values.
     */
    public static function importOne(string $locale, string $namespace, string $key, string $value): self
    {
        /** @var Translation|null $existing */
        $existing = self::query()
            ->where('locale', $locale)
            ->where('namespace', $namespace)
            ->where('key', $key)
            ->first();

        if ($existing === null) {
            $created = self::query()->create([
                'locale' => $locale,
                'namespace' => $namespace,
                'key' => $key,
                'value' => $value,
                'status' => TranslationStatus::Machine->value,
                'updated_at' => now(),
            ]);
            UiStrings::bumpVersion();

            return $created;
        }

        // Human-reviewed strings are editorial work — a re-import must
        // never silently overwrite them (11-multilingual §6.2).
        if ($existing->isMachine() && $existing->value !== $value) {
            // A reject() later must restore THIS draft, not the one the
            // previous human edit displaced.
            $existing->forceFill(['value' => $value, 'machine_value' => $value, 'updated_at' => now()])->save();
            UiStrings::bumpVersion();
        }

        return $existing;
    }
}
