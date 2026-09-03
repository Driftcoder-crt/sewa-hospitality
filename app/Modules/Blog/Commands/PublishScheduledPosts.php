<?php

namespace App\Modules\Blog\Commands;

use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Services\PostPublishGate;
use App\Support\Seo\RegenerateSitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * posts:publish-scheduled — everyMinute on the single cron (07-blog-news
 * §5): due scheduled posts publish through the gate; a gate failure
 * alerts ops and flags the post (§6 missed-slot rule).
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled {--dry-run}';

    protected $description = 'Publish due scheduled posts through the publish gate';

    public function handle(PostPublishGate $gate): int
    {
        $due = Post::query()
            ->where('status', PostStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $published = 0;

        foreach ($due as $post) {
            if ($this->option('dry-run')) {
                $this->line("would publish: {$post->title}");

                continue;
            }

            try {
                $gate->publish($post);
                $published++;

                // Reuse the queued sitemap regeneration path (syncs queue).
                RegenerateSitemap::dispatch();
            } catch (\Throwable $e) {
                Log::channel('ops')->error('Scheduled post failed its publish gate', [
                    'post' => $post->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($published > 0) {
            $this->info("posts:publish-scheduled — {$published} published.");
        }

        return self::SUCCESS;
    }
}
