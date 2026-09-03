<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::with(['author', 'category', 'tags'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('content', 'like', "%{$request->search}%")
                    ->orWhere('excerpt', 'like', "%{$request->search}%");
            });
        }

        $posts = $query->orderBy('published_at', 'desc')->paginate(12);
        $categories = Category::where('is_active', true)->withCount('posts')->get();
        $tags = Tag::withCount('posts')->latest()->take(10)->get();

        return view('blog::index', compact('posts', 'categories', 'tags'));
    }

    public function show(string $slug): View
    {
        $post = Post::with(['author', 'category', 'tags', 'comments.user'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $post->incrementViews();

        $relatedPosts = Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('blog::show', compact('post', 'relatedPosts'));
    }

    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        $posts = Post::where('category_id', $category->id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('blog::category', compact('category', 'posts'));
    }

    public function tag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        
        $posts = Post::whereHas('tags', function ($q) use ($tag) {
                $q->where('id', $tag->id);
            })
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('blog::tag', compact('tag', 'posts'));
    }
}
