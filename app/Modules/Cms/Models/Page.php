<?php

namespace App\Modules\Cms\Models;

use App\Models\Media;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Observers\PageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * CMS page (03-technical-specs/03-database-schema.md §2 "pages" +
 * 04-modules/01-cms.md). Composed entirely of typed blocks
 * (BlockRegistry); publish transitions are gated by
 * PublishGate — SEO fields, noindex confirmation and a render probe
 * (04-modules/01-cms.md §5).
 */
#[ObservedBy([PageObserver::class])]
class Page extends Model
{
    use HasUlids;
    use Searchable;

    protected $fillable = [
        'slug', 'title', 'type', 'parent_id', 'template', 'status',
        'published_at', 'scheduled_at', 'meta_title', 'meta_description',
        'og_image_media_id', 'noindex', 'noindex_reason',
        'noindex_confirmed_at', 'noindex_confirmed_by',
        'canonical_override', 'blocks', 'locale', 'locale_source_id',
        'author_user_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PageStatus::class,
            'blocks' => 'array',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'noindex' => 'boolean',
            'noindex_confirmed_at' => 'datetime',
        ];
    }

    /** @return HasMany<PageRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('created_at');
    }

    /** @return BelongsTo<Page, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'author_user_id');
    }

    /** Open Graph image (og conversion rendered by the meta service). */
    public function ogMedia()
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    /** Live, public pages only (render path scope). */
    public function scopePublished($query)
    {
        return $query->where('status', PageStatus::Published->value);
    }

    /**
     * Path this page serves, derived from type (04-modules/01-cms.md §3).
     * Home is special-cased: slug `home` serves `/`.
     */
    public function publicPath(): string
    {
        if ($this->slug === 'home') {
            return '/';
        }

        return match ($this->type) {
            PageType::Legal => '/legal/'.$this->slug,
            PageType::Landing => '/p/'.$this->slug,
            default => '/'.$this->slug,
        };
    }

    /** Scout payload (08-search §1: title, meta blocks text — legal/about). */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'meta_description' => (string) $this->meta_description,
            'path' => $this->publicPath(),
        ];
    }

    /**
     * Reserved paths that CMS slugs may never claim — system routes
     * across current and contracted future modules
     * (01-platform-vision/04-subdomains-ventures.md §paths).
     *
     * @return list<string>
     */
    public static function reservedPaths(): array
    {
        return [
            '/', 'status', 'preview', 'dev', 'search', 'feed', 'thank-you',
            'about', 'contact', 'reviews', 'services', 'cities', 'housing',
            'blog', 'news', 'careers', 'csr', 'legal', 'login', 'register',
            'admin', 'api', 'p',
        ];
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(mb_strtolower($slug), self::reservedPaths(), true);
    }

    /**
     * Ordered blocks; typed rows only — the render path skips anything
     * the registry does not know (publish probe prevents that live).
     *
     * @return list<array{type: string, data: mixed}>
     */
    public function blockList(): array
    {
        $blocks = $this->blocks ?? [];

        return is_array($blocks) ? array_values($blocks) : [];
    }
}
