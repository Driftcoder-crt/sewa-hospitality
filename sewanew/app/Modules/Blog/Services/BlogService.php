<?php

namespace App\Modules\Blog\Services;

use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogService
{
    public function getPublishedPosts(int $perPage = 12): LengthAwarePaginator
    {
        return Post::with(['author', 'category', 'tags'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getPostBySlug(string $slug): ?Post
    {
        return Post::with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }

    public function getPostsByCategory(string $categorySlug, int $perPage = 12): LengthAwarePaginator
    {
        $category = Category::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();

        return Post::where('category_id', $category->id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getPostsByTag(string $tagSlug, int $perPage = 12): LengthAwarePaginator
    {
        $tag = Tag::where('slug', $tagSlug)->firstOrFail();

        return Post::whereHas('tags', function ($query) use ($tag) {
                $query->where('id', $tag->id);
            })
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        if (!$post->category_id) {
            return collect();
        }

        return Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    public function getActiveCategories(): Collection
    {
        return Category::where('is_active', true)
            ->withCount('posts')
            ->orderBy('sort_order')
            ->get();
    }

    public function getPopularTags(int $limit = 10): Collection
    {
        return Tag::withCount('posts')
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->take($limit)
            ->get();
    }

    public function searchPosts(string $query, int $perPage = 12): LengthAwarePaginator
    {
        return Post::with(['author', 'category'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhere('meta_title', 'like', "%{$query}%")
                    ->orWhere('meta_description', 'like', "%{$query}%");
            })
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getFeaturedPosts(int $limit = 3): Collection
    {
        return Post::where('status', 'published')
            ->where('published_at', '<=', now())
            ->whereNotNull('featured_image')
            ->orderByDesc('views_count')
            ->take($limit)
            ->get();
    }

    public function getRecentPosts(int $limit = 5): Collection
    {
        return Post::with(['author', 'category'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->take($limit)
            ->get();
    }
}
