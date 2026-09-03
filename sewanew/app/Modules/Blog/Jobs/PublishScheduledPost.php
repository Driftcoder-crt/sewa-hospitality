<?php

namespace App\Modules\Blog\Jobs;

use App\Modules\Blog\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Post::where('status', 'published')
            ->where('published_at', '<=', now())
            ->get()
            ->each(function (Post $post) {
                // Trigger any post-publish events or notifications
                event(new \App\Modules\Blog\Events\PostPublished($post));
            });
    }
}
