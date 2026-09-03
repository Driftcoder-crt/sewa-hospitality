<?php

namespace App\Modules\Ai\Jobs;

use App\Modules\Ai\Services\AiGateway;
use App\Modules\Ai\Services\MergeTranslated;
use App\Modules\Ai\Services\PromptLibrary;
use App\Modules\Ai\Services\TranslatableFields;
use App\Modules\Cms\Services\SettingsRepository;
use App\Modules\I18n\Models\Locale;
use App\Support\Queue\QueueHardened;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Machine-draft translation for one content entity + target locale
 * (11-multilingual §4, queue `ai` per 07-queues-scheduling §2).
 *
 * Pipeline invariants (08-ai-system/01 §7):
 *   - IDEMPOTENT: one variant per entity-locale — a queued duplicate
 *     or a race re-run is a no-op.
 *   - HUMAN GATE: the variant lands as a DRAFT sibling; machine output
 *     can never self-publish ("publish per-locale is a deliberate
 *     action"). The only exception is the explicit auto-publish policy
 *     in settings (i18n.auto_publish_{table}, default OFF), reserved
 *     for low-risk types once quality stabilizes. RECORDED
 *     INTERPRETATION: the schema §10 machine|human states live on the
 *     translations (UI-strings) table; for CONTENT variants the same
 *     gate is expressed as draft status + the review queue in
 *     I18nManager — draft = machine draft awaiting a human.
 *   - NOTHING BLOCKS: breaker open / budget hard-stop / malformed
 *     output → the job re-leases (tries cap) and finally gives up;
 *     the EN source keeps serving untouched (§7 — EN-only is always
 *     an acceptable state).
 */
final class TranslateContent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use QueueHardened;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [120, 600];

    public function __construct(
        public readonly string $entityClass,
        public readonly string $entityId,
        public readonly string $targetLocale,
    ) {}

    public function handle(): void
    {
        if ($this->targetLocale === Locale::DEFAULT || ! Locale::isEnabled($this->targetLocale)) {
            return;
        }

        $locale = Locale::query()->find($this->targetLocale);

        if ($locale === null || ! $locale->auto_translate) {
            return;
        }

        $source = $this->entityClass::query()->find($this->entityId);

        if ($source === null || $source->locale !== Locale::DEFAULT) {
            return; // deleted while queued, or the row is itself a variant
        }

        $fields = TranslatableFields::for($this->entityClass);

        if ($fields === null) {
            return; // entity type is outside the launch translation set
        }

        // Idempotency: exactly one variant per entity-locale.
        $exists = $this->entityClass::query()
            ->where('locale_source_id', $source->getKey())
            ->where('locale', $this->targetLocale)
            ->exists();

        if ($exists) {
            return;
        }

        $payload = $this->payload($source, $fields);

        if ($payload === []) {
            return; // nothing translatable on this entity
        }

        $result = AiGateway::feature('translate')->chat(
            PromptLibrary::translateMessages(
                (string) json_encode($payload, JSON_UNESCAPED_UNICODE),
                $this->targetLocale,
            ),
            ['max_tokens' => 4000],
        );

        // Park (never block): EN keeps serving; re-lease within tries.
        if ($result === null) {
            $this->release(max($this->backoff[0], 120));

            return;
        }

        $translated = json_decode((string) ($result->content ?? ''), true);

        if (! is_array($translated)) {
            Log::channel('ops')->info('TranslateContent: malformed model output — parked', [
                'entity' => $this->entityClass,
                'id' => $this->entityId,
                'locale' => $this->targetLocale,
            ]);

            $this->release($this->backoff[1] ?? 600);

            return;
        }

        $merged = MergeTranslated::merge($payload, $translated);

        $attributes = $this->variantAttributes($source, $merged, $fields);

        $this->entityClass::query()->create($attributes);
    }

    /** Flat JSON payload of translatable values (only non-empty strings). */
    private function payload($source, array $fields): array
    {
        $payload = [];

        foreach ($fields['strings'] as $field) {
            $value = $source->getAttribute($field);

            if (is_string($value) && trim($value) !== '') {
                $payload[$field] = $value;
            }
        }

        foreach ($fields['json'] as $field) {
            $value = $source->getAttribute($field);

            if (is_array($value) && $value !== []) {
                $payload[$field] = $value;
            }
        }

        return $payload;
    }

    /** Copy the source row + merged translations + draft status. */
    private function variantAttributes($source, array $merged, array $fields): array
    {
        // deleted_at is NOT fillable on SoftDeletes models (Eloquent owns
        // it) — a raw copy would throw a MassAssignmentException.
        $skip = ['id', 'created_at', 'updated_at', 'deleted_at', 'locale', 'locale_source_id'];

        $attributes = collect($source->getAttributes())
            ->except($skip)
            ->all();

        // Raw attributes hold JSON-cast columns as their DB strings; a raw
        // copy would DOUBLE-ENCODE them on create (media_ids always, and
        // blocks/content_blocks/faq whenever they are empty and therefore
        // absent from the translation payload). Re-hydrate every JSON-cast
        // column from the model's cast value so create() encodes once.
        $jsonColumns = collect($source->getCasts())
            ->filter(fn ($cast): bool => is_string($cast)
                && in_array($cast, ['array', 'json', 'object', 'collection'], true))
            ->keys();

        foreach ($jsonColumns as $column) {
            $attributes[$column] = $source->getAttribute($column);
        }

        foreach ($fields['strings'] as $field) {
            if (array_key_exists($field, $merged)) {
                $attributes[$field] = $merged[$field];
            }
        }

        foreach ($fields['json'] as $field) {
            if (array_key_exists($field, $merged)) {
                $attributes[$field] = $merged[$field];
            }
        }

        $attributes['locale'] = $this->targetLocale;
        $attributes['locale_source_id'] = $source->getKey();

        // Human gate: draft unless the per-entity auto-publish policy is
        // explicitly ON (defaults OFF everywhere — 11-multilingual §4).
        $autoPublish = (bool) app(SettingsRepository::class)
            ->get('i18n.auto_publish_'.$source->getTable(), false);

        if (! $autoPublish) {
            $attributes['status'] = 'draft';
        }

        return $attributes;
    }
}
