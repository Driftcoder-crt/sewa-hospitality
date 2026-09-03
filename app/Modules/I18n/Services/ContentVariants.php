<?php

namespace App\Modules\I18n\Services;

use App\Modules\I18n\Models\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Translation-group resolution for content entities (11-multilingual
 * §4 fallback doctrine + schema §10 "every locale pair unique with
 * source"). Content rows carry `locale` + `locale_source_id` — the
 * SOURCE row (locale=en, locale_source_id=null) roots the group and
 * every variant points at it.
 *
 * The resolver encodes the three rules the spec makes non-negotiable:
 *   1. Requested locale wins when a PUBLISHED variant exists.
 *   2. Missing variant → the EN source renders — a localized URL NEVER
 *      404s for content that exists in EN ("EN renders, hreflang omits
 *      the locale; a blank or machine-only page NEVER publishes").
 *   3. hreflang lists exactly the locales that truly serve a variant
 *      (+ x-default → en) — "hreflang always matches reality".
 */
final class ContentVariants
{
    /**
     * Detail-page resolution: first the requested locale, then the EN
     * source. Pass a query WITHOUT a locale constraint — every other
     * constraint (slug, type, status) belongs to the caller.
     */
    public static function firstInLocale(Builder $query, ?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        if ($locale === Locale::DEFAULT) {
            return $query->first();
        }

        return (clone $query)->where('locale', $locale)->first()
            ?? $query->where('locale', Locale::DEFAULT)->first();
    }

    /**
     * List resolution: current-locale variants plus EN sources that
     * have no variant in the requested locale (a localized listing
     * never HIDES content that exists in EN — it renders the EN title).
     * Returns the query unchanged when already serving en.
     */
    public static function localizedList(Builder|BelongsToMany $query, ?string $locale = null): Builder
    {
        // Scope chains on a belongsToMany (e.g. $tag->posts()->published())
        // return the RELATION object, not the underlying builder — unwrap it.
        if ($query instanceof BelongsToMany) {
            $query = $query->getQuery();
        }

        $locale ??= app()->getLocale();

        if ($locale === Locale::DEFAULT) {
            return $query->where('locale', $locale);
        }

        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $w) use ($table, $locale): void {
            $w->where($table.'.locale', $locale)
                ->orWhere(function (Builder $en) use ($table, $locale): void {
                    $en->where($table.'.locale', Locale::DEFAULT)
                        // whereExists hands its closure a Query\Builder (the
                        // raw one), not an Eloquent Builder — type accordingly.
                        ->whereNotExists(function (\Illuminate\Database\Query\Builder $variant) use ($table, $locale): void {
                            $variant->selectRaw(1)
                                ->from($table.' as i18n_variants')
                                ->whereColumn('i18n_variants.locale_source_id', $table.'.id')
                                ->where('i18n_variants.locale', $locale);
                        });
                });
        });
    }

    /**
     * hreflang alternates for an entity's translation group:
     * locale-code → path for every PUBLISHED member + `x-default` →
     * the EN source path. Locales without a published variant are
     * omitted (the fallback page renders EN but must not advertise
     * itself as a ja URL — Google reads the EN URL instead).
     *
     * @return array<string, string> e.g. ['en' => '/services/x', 'ja' => '/ja/services/x', 'x-default' => '/services/x']
     */
    public static function alternatesFor(Model $entity): array
    {
        $sourceId = $entity->locale_source_id ?? $entity->getKey();

        /** @var Collection<int, Model> $members */
        $members = $entity::query()
            ->where($entity->getKeyName(), $sourceId)
            ->orWhere('locale_source_id', $sourceId)
            ->get();

        $alternates = [];

        foreach ($members as $member) {
            if (! self::isPublished($member)) {
                continue;
            }

            $alternates[(string) $member->locale] = (string) $member->publicPath();
        }

        if (isset($alternates[Locale::DEFAULT])) {
            $alternates['x-default'] = $alternates[Locale::DEFAULT];
        }

        return $alternates;
    }

    /** Published check across the content entities' status conventions. */
    private static function isPublished(Model $member): bool
    {
        $attributes = $member->getAttributes();

        if (array_key_exists('status', $attributes)) {
            $status = $member->getAttribute('status');

            return ($status instanceof \BackedEnum ? $status->value : (string) $status) === 'published';
        }

        if (array_key_exists('published', $attributes)) {
            return (bool) $member->getAttribute('published');
        }

        return true;
    }
}
