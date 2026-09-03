<?php

namespace App\Modules\Leads\Livewire\Concerns;

use App\Modules\Leads\Services\FormGuard;
use App\Support\Locks\Exceptions\IdempotencyConflictException;
use App\Support\Locks\IdempotencyStore;
use App\Support\Security\TurnstileVerifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The shared spine of every public lead form (04-modules/03-leads-crm
 * §3): honeypot + time-trap → rate limits → Turnstile → validation →
 * idempotent transactional write → honest success. Inline errors never
 * leak internals; a bot that fills the honeypot sees a fake success so
 * it learns nothing (the write silently does not happen).
 */
trait InteractsWithLeadForms
{
    /** Honeypot — visually hidden, bots love filling everything. */
    public string $websiteUrl = '';

    /** Turnstile token (widget callback fills it). */
    public string $turnstileToken = '';

    /** Per-open idempotency key (ULID) — double-click/retry safe. */
    public string $idempotencyKey = '';

    /** Time-trap anchor (component open time, ms). */
    public float $openedAt = 0;

    /** idle | success */
    public string $status = 'idle';

    /** utm_* capture from the landing URL (client-stamped JSON). */
    public string $utmJson = '';

    public function mountLeadForm(): void
    {
        $this->idempotencyKey = (string) Str::ulid();
        $this->openedAt = microtime(true);
        $this->restoreDraft();
    }

    /** Rate-limit bucket, e.g. 'contact', 'quote', 'callback'. */
    abstract protected function bucket(): string;

    /** Current field values for the draft store + fingerprint. */
    abstract protected function draftFields(): array;

    /** Validation rule set. */
    abstract protected function formRules(): array;

    /** The guarded write — returns the created model. */
    abstract protected function execute(): mixed;

    /** Success handling — redirect or inline confirmation. */
    abstract protected function handleSuccess(mixed $result, bool $replayed): void;

    /** Apply a draft array to component properties (per form). */
    abstract protected function fillDraft(array $draft): void;

    /** Persist fields while the visitor types (never lose typed data). */
    public function updatedForm(): void
    {
        $this->persistDraft();
    }

    public function submitLead(): void
    {
        if ($this->status === 'success') {
            return;
        }

        // 1) Honeypot + time-trap — a bot gets a convincing fake success.
        if (! FormGuard::human($this->websiteUrl, $this->openedAt)) {
            Log::channel('ops')->warning('Lead form: honeypot/time-trap tripped — silent fake success', [
                'bucket' => $this->bucket(),
                'ip' => request()->ip(),
            ]);
            $this->status = 'success';

            return;
        }

        // 2) Layered rate limits (5/min + 20/h per IP, error lock #3).
        if (! FormGuard::allowed($this->bucket())) {
            $this->addError('form', 'Too many attempts from your network — please wait a minute and try again.');

            return;
        }

        // 3) Turnstile (grace mode degrades on Cloudflare outage).
        if (! TurnstileVerifier::verify($this->turnstileToken, request()->ip())) {
            $this->addError('form', 'Human verification failed — please retry the check box.');

            return;
        }

        // 4) Validation — inline, actionable, typed data preserved.
        $this->validate($this->formRules());

        // 5) Idempotent transactional write (the money path).
        try {
            $result = IdempotencyStore::remember(
                key: $this->idempotencyKey,
                requestFingerprint: $this->fingerprint(),
                task: fn (): mixed => $this->execute(),
            );
        } catch (IdempotencyConflictException) {
            $this->addError('form', 'This exact submission is already recorded — no need to send it twice.');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->addError('form', 'We could not save your submission just now — please retry in a moment.');

            return;
        }

        $this->forgetDraft();
        $this->status = 'success';
        $this->handleSuccess($result['result'], $result['replayed']);
    }

    /** Stable request fingerprint for the idempotency store. */
    protected function fingerprint(): string
    {
        return sha1(static::class.'|'.json_encode($this->draftFields()));
    }

    protected function draftSessionKey(): string
    {
        return 'sewa.form.draft.'.static::class;
    }

    protected function persistDraft(): void
    {
        session()->put($this->draftSessionKey(), $this->draftFields());
    }

    protected function restoreDraft(): void
    {
        $draft = session()->get($this->draftSessionKey());

        if (is_array($draft)) {
            $this->fillDraft($draft);
        }
    }

    protected function forgetDraft(): void
    {
        session()->forget($this->draftSessionKey());
    }

    /** UTM json → array (client captures location.search). */
    protected function utm(): ?array
    {
        $decoded = json_decode($this->utmJson, true);

        if (! is_array($decoded)) {
            return null;
        }

        $utm = array_filter(
            $decoded,
            fn (string $key): bool => str_starts_with($key, 'utm_'),
            ARRAY_FILTER_USE_KEY,
        );

        return $utm === [] ? null : array_map(strval(...), $utm);
    }
}
