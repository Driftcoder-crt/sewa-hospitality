<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Jobs\TranslateContent;
use App\Modules\I18n\Models\Locale;
use Illuminate\Database\Eloquent\Model;

/**
 * Fan-out for the translation pipeline (11-multilingual §4 step 2):
 * "editor publishes an entity in en → enqueues TranslateContent per
 * enabled locale". One call site per publish path — observers/events
 * delegate here so the dispatch rules can never drift.
 */
final class TranslationDispatcher
{
    /**
     * Dispatch a machine-draft job for every enabled auto-translate
     * locale. EN sources only; the kill switch short-circuits (no AI,
     * nothing enqueued — content publishes EN-only, nothing blocked).
     *
     * @return int jobs dispatched
     */
    public static function forEntity(Model $entity): int
    {
        if (! AiGateway::globallyEnabled()) {
            return 0;
        }

        if ($entity->getAttribute('locale') !== Locale::DEFAULT) {
            return 0; // only EN sources root translation groups
        }

        if (TranslatableFields::for($entity::class) === null) {
            return 0;
        }

        $count = 0;

        foreach (Locale::query()->enabled()->translatable()->pluck('code') as $code) {
            TranslateContent::dispatch($entity::class, (string) $entity->getKey(), (string) $code);
            $count++;
        }

        return $count;
    }
}
