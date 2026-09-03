<?php

declare(strict_types=1);

namespace Modules\Cms\Jobs;

use Modules\Cms\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Sitemap Job
 */
class GenerateSitemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $limit = 1000
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting sitemap generation...');

        $pages = Page::published()
            ->where('no_index', false)
            ->limit($this->limit)
            ->get();

        $sitemapContent = view('cms.sitemap.xml', [
            'pages' => $pages,
        ])->render();

        // Save sitemap to storage
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            'sitemap.xml',
            $sitemapContent
        );

        Log::info("Sitemap generated with {$pages->count()} pages.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Sitemap generation failed: ' . $exception->getMessage());
    }
}
