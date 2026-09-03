<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CMS Redirect Model
 * 
 * Manages URL redirects for SEO and site maintenance.
 */
class Redirect extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_redirects';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'is_active',
        'expires_at',
        'hit_count',
        'last_hit_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
            'expires_at' => 'datetime',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Redirect $redirect) {
            // Ensure from_path starts with /
            if (!str_starts_with($redirect->from_path, '/')) {
                $redirect->from_path = '/' . $redirect->from_path;
            }

            // Ensure to_path starts with / or http
            if (!str_starts_with($redirect->to_path, '/') && !str_starts_with($redirect->to_path, 'http')) {
                $redirect->to_path = '/' . $redirect->to_path;
            }
        });
    }

    /**
     * Get the creator of the redirect.
     */
    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if redirect is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if redirect is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Record a hit for this redirect.
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_hit_at' => now()]);
    }

    /**
     * Get available status codes.
     */
    public static function getStatusCodes(): array
    {
        return [
            301 => 'Moved Permanently',
            302 => 'Found (Temporary)',
            303 => 'See Other',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
        ];
    }

    /**
     * Find active redirect by path.
     */
    public static function findByPath(string $path): ?self
    {
        // Normalize path
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return static::where('from_path', $path)
            ->orWhere('from_path', rtrim($path, '/') . '/*')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Create a redirect.
     */
    public static function createRedirect(
        string $from,
        string $to,
        int $statusCode = 301,
        ?int $userId = null
    ): self {
        return static::create([
            'from_path' => $from,
            'to_path' => $to,
            'status_code' => $statusCode,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    /**
     * Scope to get active redirects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired redirects.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to search redirects.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('from_path', 'like', "%{$search}%")
                ->orWhere('to_path', 'like', "%{$search}%");
        });
    }
}
