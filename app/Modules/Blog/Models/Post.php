<?php

namespace App\Modules\Blog\Models;

use App\Models\Media;
use App\Models\User;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Enums\PostType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * A blog/news post (07-blog-news §2–3). URL rules: blog = dated
 * /blog/{yyyy}/{mm}/{slug}; news = /news/{slug}. Related logic is
 * deterministic (category → tag overlap → recent) and lives here so
 * every surface renders the same set.
 */
class Post extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'type', 'title', 'excerpt', 'body', 'cover_media_id',
        'status', 'published_at', 'scheduled_at', 'author_user_id',
        'approved_by_user_id', 'review_notes', 'locale_source_id',
        'canonical', 'meta_title', 'meta_description', 'noindex', 'locale',
        'reading_time', 'word_count', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'noindex' => 'boolean',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        // The spec pivot is tag_post (03-database-schema §5) — Laravel's
        // alphabetical guess (post_tag) misses the table entirely.
        return $this->belongsToMany(Tag::class, 'tag_post', 'post_id', 'tag_id');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeType(Builder $query, PostType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /* ── URLs + computed copy ──────────────────────────────────────── */

    /** /blog/{yyyy}/{mm}/{slug} for blog; /news/{slug} for news. */
    public function publicPath(): string
    {
        if ($this->type === PostType::News) {
            return '/news/'.$this->slug;
        }

        return sprintf(
            '/blog/%s/%s/%s',
            $this->published_at?->format('Y') ?? '0000',
            $this->published_at?->format('m') ?? '00',
            $this->slug,
        );
    }

    /** Display name with credentials hint (E-E-A-T byline block). */
    public function authorLabel(): string
    {
        $profile = $this->author?->authorProfile;

        return $profile?->title
            ? "{$this->author?->name} — {$profile->title}"
            : (string) $this->author?->name;
    }

    /* ── Related (deterministic, explainable — 07 doc §5) ──────────── */

    /**
     * Same category first, then tag overlap, backfill recent.
     *
     * @return Collection<int, self>
     */
    public function related(int $count = 3): Collection
    {
        $categoryIds = $this->categories()->pluck('categories.id');
        $tagIds = $this->tags()->pluck('tags.id');

        $base = self::query()
            ->published()
            ->where('locale', $this->locale)
            ->whereKeyNot($this->getKey());

        $byCategory = (clone $base)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->orderByDesc('published_at')
            ->limit($count)
            ->get();

        if ($byCategory->count() >= $count) {
            return $byCategory;
        }

        $picked = $byCategory->pluck('id');

        $byTags = $tagIds->isNotEmpty()
            ? (clone $base)->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->whereNotIn('id', $picked)->orderByDesc('published_at')->limit($count - $byCategory->count())->get()
            : collect();

        $picked = $picked->merge($byTags->pluck('id'));

        if ($byCategory->count() + $byTags->count() < $count) {
            $recent = (clone $base)
                ->whereNotIn('id', $picked)
                ->orderByDesc('published_at')
                ->limit($count - $byCategory->count() - $byTags->count())
                ->get();

            $byTags = $byTags->merge($recent);
        }

        return $byCategory->merge($byTags)->take($count);
    }

    /* ── Computed copy metrics (07 doc §5) ─────────────────────────── */

    /** Compute word_count + reading_time from body (call before save). */
    public function computeCopyMetrics(): void
    {
        $text = trim(strip_tags((string) $this->body));
        $words = $text === '' ? 0 : str_word_count(str_replace(["\xC2\xA0", '—'], ' ', $text));

        $this->word_count = $words;
        $this->reading_time = max(1, (int) ceil($words / 220));
    }
}
