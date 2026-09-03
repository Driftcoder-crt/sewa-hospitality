<?php

namespace App\Modules\Blog\Livewire;

use App\Models\User;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Enums\PostType;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Services\PostPublishGate;
use App\Support\Audit\ActivityLogger;
use App\Support\Seo\RegenerateSitemap;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Posts table (07-blog-news §4.1): filters, status chips, review
 * workflow actions (submit → approve → publish), the "needs review"
 * list. Authors see their own posts only (policy-scoped query).
 */
#[Layout('layouts.admin')]
class PostsTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $q = '';

    #[Url]
    public bool $needsReview = false;

    public string $title = '';

    public string $postType = 'blog';

    public string $authorId = '';

    public bool $showForm = false;

    public function create(): void
    {
        $this->authorize('create', Post::class);

        $this->validate([
            'title' => ['required', 'string', 'max:190'],
            'postType' => ['required', 'in:blog,news'],
            'authorId' => ['required', 'exists:users,id'],
        ]);

        $post = Post::query()->create([
            'slug' => Str::slug($this->title).'-'.Str::lower(Str::random(4)),
            'type' => $this->postType,
            'title' => $this->title,
            'excerpt' => '',
            'body' => '',
            'status' => PostStatus::Draft,
            'author_user_id' => $this->authorId,
            'locale' => app()->getLocale(),
            'created_by' => auth()->id(),
        ]);

        ActivityLogger::log('admin', 'create', $post, ['title' => $post->title]);
        $this->reset('title', 'showForm');

        $this->redirect(route('admin.posts.edit', ['post' => $post->getKey()]), navigate: true);
    }

    /** Author action: draft → review (PostPolicy::update gates it). */
    public function submitForReview(string $id): void
    {
        $post = Post::query()->findOrFail($id);
        $this->authorize('update', $post);

        try {
            app(PostPublishGate::class)->submitForReview($post, auth()->user());
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['workflow' => $e->getMessage()]);
        }

        $this->dispatch('notify', tone: 'success', message: 'Submitted for review.');
    }

    /** Editor action: review → approved (four-eyes enforced in the gate). */
    public function approve(string $id): void
    {
        $post = Post::query()->findOrFail($id);
        $this->authorize('review', Post::class);

        try {
            app(PostPublishGate::class)->approve($post, auth()->user());
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['workflow' => $e->getMessage()]);
        }

        $this->dispatch('notify', tone: 'success', message: 'Approved — ready to publish.');
    }

    /** Editor action: publish through the gate (or surface gate errors). */
    public function publish(string $id): void
    {
        $post = Post::query()->findOrFail($id);
        $this->authorize('publish', $post);

        $errors = app(PostPublishGate::class)->validate($post);

        if ($post->status === PostStatus::Review && $post->approved_by_user_id === null) {
            $errors['workflow'] = 'Approve before publishing (review workflow).';
        }

        if ($errors !== []) {
            $this->dispatch('notify', tone: 'error', message: 'Publish gate: '.implode(' ', array_slice($errors, 0, 2)));

            return;
        }

        app(PostPublishGate::class)->publish($post);
        $post->refresh();
        ActivityLogger::log('admin', 'publish', $post, ['published_at' => $post->published_at?->toIso8601String()]);
        RegenerateSitemap::dispatch();

        $this->dispatch('notify', tone: 'success', message: 'Published at '.$post->publicPath());
    }

    public function render(): View
    {
        $this->authorize('viewAny', Post::class);

        $user = auth()->user();

        $query = Post::query()
            ->with(['author:id,name', 'cover:id', 'categories:id,name'])
            // Authors hold blog.view for reading, but only reviewers
            // (blog.publish — the four-eyes gate) see the whole table;
            // everyone else is scoped to their own posts.
            ->when(! $user->can('review', Post::class), fn ($q) => $q->where('author_user_id', $user->getKey()))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->q !== '', fn ($q) => $q->where('title', 'like', '%'.$this->q.'%'))
            ->when($this->needsReview, fn ($q) => $q->where('status', PostStatus::Review))
            ->orderByRaw("case status when 'review' then 0 when 'scheduled' then 1 when 'draft' then 2 when 'published' then 3 else 4 end")
            ->orderByDesc('updated_at');

        return view('blog.livewire.posts-table', [
            'posts' => $query->paginate(15),
            'statuses' => PostStatus::options(),
            'types' => PostType::options(),
            'authors' => User::query()->role('author')->orderBy('name')->get(['id', 'name']),
            'canReview' => $user->can('review', Post::class),
            'reviewCount' => Post::query()->where('status', PostStatus::Review)->count(),
        ]);
    }
}
