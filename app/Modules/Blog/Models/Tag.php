<?php

namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Per-post tag (03-database-schema §4) — tags appear ONLY on their own
 * posts and their own archive; never a sitewide cloud. */
class Tag extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['slug', 'name'];

    public function posts(): BelongsToMany
    {
        // The spec pivot is tag_post (03-database-schema §5) — Laravel's
        // alphabetical guess (post_tag) misses the table entirely (mirrors
        // Post::tags() above the same table).
        return $this->belongsToMany(Post::class, 'tag_post', 'tag_id', 'post_id');
    }

    public function publicPath(): string
    {
        return '/blog/tag/'.$this->slug;
    }
}
