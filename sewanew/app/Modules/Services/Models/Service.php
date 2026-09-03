<?php

namespace App\Modules\Services\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends BaseModel
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'icon',
        'category_id',
        'price',
        'currency',
        'duration_minutes',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ServiceFeature::class, 'service_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'service_city')
                    ->withPivot('price_variation', 'is_available')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . strtoupper($this->currency ?? 'USD');
    }

    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_minutes >= 60) {
            $hours = floor($this->duration_minutes / 60);
            $minutes = $this->duration_minutes % 60;
            
            if ($minutes > 0) {
                return "{$hours}h {$minutes}m";
            }
            
            return "{$hours}h";
        }
        
        return "{$this->duration_minutes}m";
    }
}
