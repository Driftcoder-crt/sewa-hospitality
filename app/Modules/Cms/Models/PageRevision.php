<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Page revision (04-modules/01-cms.md §4.7): immutable JSON snapshot of
 * the page's content fields at save time. Restore = copy snapshot onto
 * the page and save → which itself writes a new revision.
 */
class PageRevision extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = ['page_id', 'snapshot', 'author_user_id'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
