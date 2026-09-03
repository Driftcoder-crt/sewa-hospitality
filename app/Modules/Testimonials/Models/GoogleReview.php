<?php

namespace App\Modules\Testimonials\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * GBP sync cache (03-database-schema §6): idempotent by external_id —
 * a double cron run can never duplicate a Google review (08 doc §8).
 */
class GoogleReview extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['external_id', 'rating', 'text', 'reviewer', 'review_at', 'url', 'fetched_at', 'synced'];

    protected function casts(): array
    {
        return [
            'review_at' => 'datetime',
            'fetched_at' => 'datetime',
            'synced' => 'boolean',
            'rating' => 'integer',
        ];
    }
}
