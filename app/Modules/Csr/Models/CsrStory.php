<?php

namespace App\Modules\Csr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A citable CSR impact story (09 doc §3): photos, partner attribution,
 * optional cross-post into the blog feed. */
class CsrStory extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'body', 'media_ids', 'ngo_partner_id',
        'published_at', 'status', 'cross_post_to_blog', 'locale',
        'locale_source_id', 'author_user_id',
    ];

    protected function casts(): array
    {
        return [
            'media_ids' => 'array',
            'published_at' => 'datetime',
            'cross_post_to_blog' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(NgoPartner::class, 'ngo_partner_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function publicPath(): string
    {
        return '/csr/'.$this->slug;
    }
}
