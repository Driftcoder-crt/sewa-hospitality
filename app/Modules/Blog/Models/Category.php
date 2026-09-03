<?php

namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Nested category (03-database-schema §4): editable, indexable
 * archive intro (07 doc §3); the 15 reference categories seed in M4-e. */
class Category extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['slug', 'name', 'parent_id', 'description', 'meta_title', 'meta_description', 'locale', 'sort'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function posts(): BelongsToMany
    {
        // category_post matches the alphabetical convention, but name the
        // keys explicitly so both blog pivots read the same way.
        return $this->belongsToMany(Post::class, 'category_post', 'category_id', 'post_id');
    }

    /** Published-post count for the category list sidebar. */
    public function publishedCount(): int
    {
        return $this->posts()->published()->count();
    }

    public function publicPath(): string
    {
        return '/blog/category/'.$this->slug;
    }
}
