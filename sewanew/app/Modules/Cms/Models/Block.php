<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CMS Block Model
 * 
 * Represents a reusable content block for page building.
 */
class Block extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_blocks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'page_id',
        'data',
        'order',
        'is_active',
        'template',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the creator of the block.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the updater of the block.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get revisions for this block.
     */
    public function revisions(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get block types.
     */
    public static function getTypes(): array
    {
        return [
            'hero' => 'Hero Section',
            'features' => 'Features Grid',
            'testimonials' => 'Testimonials Carousel',
            'cta' => 'Call to Action',
            'content' => 'Rich Content',
            'gallery' => 'Image Gallery',
            'video' => 'Video Section',
            'stats' => 'Statistics',
            'team' => 'Team Members',
            'faq' => 'FAQ Accordion',
            'contact' => 'Contact Form',
            'newsletter' => 'Newsletter Signup',
        ];
    }

    /**
     * Get available templates for type.
     */
    public static function getTemplates(string $type): array
    {
        $templates = [
            'hero' => ['default', 'fullscreen', 'split', 'centered'],
            'features' => ['grid', 'list', 'cards', 'alternating'],
            'testimonials' => ['carousel', 'grid', 'list'],
            'cta' => ['default', 'banner', 'modal'],
            'content' => ['default', 'two-column', 'with-sidebar'],
            'gallery' => ['grid', 'masonry', 'carousel'],
            'video' => ['default', 'background', 'modal'],
            'stats' => ['default', 'counters', 'progress'],
            'team' => ['grid', 'list', 'cards'],
            'faq' => ['accordion', 'toggle', 'list'],
            'contact' => ['default', 'minimal', 'with-map'],
            'newsletter' => ['default', 'inline', 'popup'],
        ];

        return $templates[$type] ?? ['default'];
    }

    /**
     * Render the block.
     */
    public function render(): string
    {
        $viewName = "cms.blocks.{$this->type}.{$this->template}";
        
        if (view()->exists($viewName)) {
            return view($viewName, ['block' => $this])->render();
        }

        // Fallback to default template
        $defaultView = "cms.blocks.{$this->type}.default";
        
        if (view()->exists($defaultView)) {
            return view($defaultView, ['block' => $this])->render();
        }

        return '<!-- Block template not found -->';
    }

    /**
     * Scope to get active blocks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get blocks by type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order blocks.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
