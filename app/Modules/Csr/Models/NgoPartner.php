<?php

namespace App\Modules\Csr\Models;

use App\Models\Media;
use App\Modules\Csr\Enums\PartnerStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An NGO partner (09 doc §4.1): named, linked to the official site,
 * one measurable claim with as-of dating (claims ledger). Archived
 * partners render under "past associations".
 */
class NgoPartner extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo_media_id', 'website', 'description',
        'focus_areas', 'claim', 'claim_as_of', 'claim_source', 'since',
        'city', 'status', 'sort', 'locale', 'locale_source_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'focus_areas' => 'array',
            'since' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function stories(): HasMany
    {
        return $this->hasMany(CsrStory::class);
    }

    /** External links carry rel="noopener" (sanitizer parity, §8 test). */
    public function externalUrl(): ?string
    {
        return $this->website;
    }
}
