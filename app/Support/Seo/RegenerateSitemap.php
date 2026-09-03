<?php

namespace App\Support\Seo;

use App\Modules\Cms\Services\SitemapGenerator;
use App\Support\Queue\QueueHardened;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Publish → sitemap regeneration (least-deviation resolution #5 in the
 * worklog: nightly 02:00 is the safety net, publishes regenerate too).
 * A standard queued job: directly dispatchable from commands/Livewire
 * (PostsTable, CsrManager, PublishScheduledPosts) AND registered as an
 * event listener for the *Published events — one class, both entries,
 * queue-safe either way.
 */
class RegenerateSitemap implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use QueueHardened;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct()
    {
        // Queued on `syncs` (short, idempotent, retried) so publish
        // actions stay fast on shared hosting. Constructor assignment,
        // NOT a property redeclaration — Queueable declares $queue
        // untyped and a differing redeclaration is a composition fatal.
        $this->onQueue('syncs');
    }

    /**
     * Job entry: no arguments. The event-listener registration invokes
     * handle($event) via CallQueuedListener — the optional parameter
     * accepts and ignores it, one signature for both entries.
     */
    public function handle(mixed $event = null): void
    {
        try {
            app(SitemapGenerator::class)->write();
        } catch (\Throwable $e) {
            // Sitemap failure must never break a publish (audit rule);
            // logged + visible on Pulse via the exception report.
            Log::warning('sitemap regeneration failed', ['error' => $e->getMessage()]);
            report($e);
        }
    }
}
