<?php

namespace App\Modules\Testimonials\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One review-request chain per move (08 doc §4.3): queued → sent →
 * (single polite follow-up after 7 days) → done. The move_reference
 * UNIQUE key makes the one-request-per-move invariant structural —
 * not a convention.
 */
class ReviewRequest extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'move_record_id', 'move_reference', 'recipient_email',
        'recipient_name', 'status', 'attempts', 'sent_at',
        'follow_up_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'completed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
