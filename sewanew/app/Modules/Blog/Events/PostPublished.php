<?php

namespace App\Modules\Blog\Events;

use App\Modules\Blog\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post
    ) {}
}
