<?php

namespace App\Modules\Careers\Models;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Author profile (03-database-schema §4): one per `author`-role user.
 * Feeds blog bylines + Person schema (M4) and the optional author
 * credit page. is_public=false hides the byline profile page but does
 * NOT erase the authorship requirement — posts still need a human.
 */
class AuthorProfile extends Model
{
    protected $table = 'author_profiles';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'title', 'bio', 'credentials', 'linkedin', 'photo_media_id', 'is_public'];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }
}
