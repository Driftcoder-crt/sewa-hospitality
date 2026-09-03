<?php

namespace App\Modules\Blog\Livewire;

use App\Models\User;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Tag;
use App\Modules\Blog\Services\PostPublishGate;
use App\Support\Audit\ActivityLogger;
use App\Support\Cms\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Post editor (07-blog-news §4.2): sanitized body with heading-ladder
 * enforcement, excerpt counter, category/tags pickers, REQUIRED human
 * author, type toggle, SEO drawer with counters, publish/schedule —
 * plus the review notes thread.
 */
#[Layout('layouts.admin')]
class PostEditor extends Component
{
    public Post $post;

    public string $title = '';

    public string $slug = '';

    public string $type = 'blog';

    public string $authorId = '';

    public string $excerpt = '';

    public string $body = '';

    public string $coverMediaId = '';

    public string $metaTitle = '';

    public string $metaDescription = '';

    public bool $noindex = false;

    public string $canonical = '';

    public ?string $scheduledAt = null;

    public string $reviewNote = '';

    /** @var array<int, string> */
    public array $categoryIds = [];

    public string $tagsCsv = '';

    public function mount(Post $post): void
    {
        $this->authorize('view', $post);
        $this->syncForm();
    }

    public function syncForm(): void
    {
        $this->title = $this->post->title;
        $this->slug = $this->post->slug;
        $this->type = $this->post->type->value;
        $this->authorId = (string) $this->post->author_user_id;
        $this->excerpt = (string) $this->post->excerpt;
        $this->body = (string) $this->post->body;
        $this->coverMediaId = (string) ($this->post->cover_media_id ?? '');
        $this->metaTitle = (string) ($this->post->meta_title ?? '');
        $this->metaDescription = (string) ($this->post->meta_description ?? '');
        $this->noindex = (bool) ($this->post->noindex ?? false);
        $this->canonical = (string) ($this->post->canonical ?? '');
        $this->scheduledAt = $this->post->scheduled_at?->format('Y-m-d\TH:i');
        $this->categoryIds = $this->post->categories()->pluck('categories.id')->all();
        $this->tagsCsv = $this->post->tags()->pluck('name')->implode(', ');
    }

    public function save(): void
    {
        $this->authorize('update', $this->post);

        $this->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9-]+$/'],
            'type' => ['required', 'in:blog,news'],
            'authorId' => ['required', 'exists:users,id'],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:120000'],
            'coverMediaId' => ['nullable', 'exists:media,id'],
            'metaTitle' => ['nullable', 'string', 'max:190'],
            'metaDescription' => ['nullable', 'string', 'max:320'],
            'canonical' => ['nullable', 'url:http,https', 'max:300'],
            'scheduledAt' => ['nullable', 'date', 'after:now - 1 day'],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['exists:categories,id'],
        ]);

        $this->post->forceFill([
            'title' => $this->title,
            'slug' => Str::slug($this->slug),
            'type' => $this->type,
            'author_user_id' => $this->authorId,
            'excerpt' => $this->excerpt,
            'body' => HtmlSanitizer::clean($this->body),
            'cover_media_id' => $this->coverMediaId ?: null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'noindex' => $this->noindex,
            'canonical' => $this->canonical ?: null,
            'scheduled_at' => $this->scheduledAt ? Carbon::parse($this->scheduledAt) : null,
            'updated_by' => auth()->id(),
        ]);

        $this->post->computeCopyMetrics();
        $this->post->save();

        $this->post->categories()->sync($this->categoryIds);
        $this->syncTags();

        ActivityLogger::log('admin', 'update', $this->post, ['title' => $this->title]);
        $this->syncForm();
        $this->dispatch('notify', tone: 'success', message: 'Post saved.');
    }

    /** Per-post tags only: existing names reuse, new names create. */
    private function syncTags(): void
    {
        $names = array_values(array_filter(array_map('trim', explode(',', $this->tagsCsv))));

        $ids = collect($names)->map(function (string $name): string {
            $slug = Str::slug($name);

            return Tag::query()->firstOrCreate(['slug' => $slug], ['name' => $name])->getKey();
        })->all();

        $this->post->tags()->sync($ids);
    }

    public function addReviewNote(): void
    {
        $this->authorize('view', $this->post);
        $this->validate(['reviewNote' => ['required', 'string', 'min:2', 'max:2000']]);

        $note = trim((string) $this->post->review_notes);
        $line = '['.now()->format('d M H:i').' · '.auth()->user()->name.'] '.$this->reviewNote;

        $this->post->forceFill(['review_notes' => $note === '' ? $line : $note."\n".$line])->save();
        $this->reviewNote = '';
        $this->dispatch('notify', tone: 'success', message: 'Review note added.');
    }

    /** Gate preview for the drawer: what would block publish right now? */
    #[Computed]
    public function gateReport(): array
    {
        return app(PostPublishGate::class)->validate($this->post);
    }

    public function render(): View
    {
        $this->authorize('view', $this->post);

        return view('blog.livewire.post-editor', [
            'categories' => Category::query()->orderBy('sort')->get(['id', 'name', 'parent_id']),
            'authors' => User::query()->role('author')->orderBy('name')->get(['id', 'name']),
            'wordCount' => str_word_count(trim(strip_tags($this->body))),
            'readingTime' => max(1, (int) ceil(str_word_count(trim(strip_tags($this->body))) / 220)),
        ]);
    }
}
