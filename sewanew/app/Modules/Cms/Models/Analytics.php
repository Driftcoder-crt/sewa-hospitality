<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CMS Analytics Model
 * 
 * Tracks page views, clicks, and conversion events.
 */
class Analytics extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_analytics';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'event_type',
        'metadata',
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the trackable model.
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who triggered the event.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get available event types.
     */
    public static function getEventTypes(): array
    {
        return [
            'view' => 'Page View',
            'click' => 'Click Event',
            'scroll' => 'Scroll Depth',
            'conversion' => 'Conversion',
            'form_submit' => 'Form Submission',
            'download' => 'File Download',
            'video_play' => 'Video Play',
            'video_complete' => 'Video Complete',
            'search' => 'Search Query',
            'exit' => 'Exit Intent',
        ];
    }

    /**
     * Track a page view.
     */
    public static function trackView(
        Model $trackable,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $referrer = null
    ): self {
        return static::create([
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'event_type' => 'view',
            'session_id' => $sessionId ?? session()->getId(),
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => $referrer ?? request()->headers->get('referer'),
        ]);
    }

    /**
     * Track a click event.
     */
    public static function trackClick(
        Model $trackable,
        string $elementId,
        array $metadata = [],
        ?string $sessionId = null
    ): self {
        return static::create([
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'event_type' => 'click',
            'metadata' => array_merge($metadata, ['element_id' => $elementId]),
            'session_id' => $sessionId ?? session()->getId(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->url(),
        ]);
    }

    /**
     * Track a conversion.
     */
    public static function trackConversion(
        Model $trackable,
        string $conversionType,
        float $value = 0,
        array $metadata = []
    ): self {
        return static::create([
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'event_type' => 'conversion',
            'metadata' => array_merge($metadata, [
                'conversion_type' => $conversionType,
                'value' => $value,
            ]),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get unique visitors count.
     */
    public function scopeUniqueVisitors($query)
    {
        return $query->selectRaw('COUNT(DISTINCT session_id)');
    }

    /**
     * Scope to get by event type.
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to get by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent events.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
