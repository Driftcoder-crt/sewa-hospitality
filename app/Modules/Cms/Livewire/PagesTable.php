<?php

namespace App\Modules\Cms\Livewire;

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Events\PagePublished;
use App\Modules\Cms\Events\PageUnpublished;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PublishGate;
use App\Modules\Cms\Services\RevisionManager;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pages admin (04-modules/01-cms.md §4.1): table with status/type
 * filters + row actions (edit, duplicate, unpublish, view) and
 * create. All lists server-paginated (admin-panel doc §2); every
 * mutation lands in the audit trail (error-locks doctrine).
 */
#[Layout('layouts.admin')]
class PagesTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $q = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function create(PublishGate $gate): void
    {
        $this->authorize('create', Page::class);

        $page = Page::query()->create([
            'title' => 'Untitled page',
            'slug' => 'untitled-'.mb_strtolower(Str::random(6)),
            'type' => PageType::Standard->value,
            'status' => PageStatus::Draft->value,
            'blocks' => [],
            'locale' => 'en',
            'updated_by' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        ActivityLogger::log('admin', 'create', $page, ['title' => $page->title]);

        $this->redirectRoute('admin.pages.edit', ['page' => $page->getKey()]);
    }

    public function duplicate(string $pageId): void
    {
        $this->authorize('create', Page::class);

        $source = Page::query()->findOrFail($pageId);

        $copy = Page::query()->create([
            'title' => $source->title.' (copy)',
            'slug' => $source->slug.'-copy-'.mb_strtolower(Str::random(4)),
            'type' => $source->type,
            'status' => PageStatus::Draft->value,
            'meta_title' => $source->meta_title,
            'meta_description' => $source->meta_description,
            'blocks' => $source->blocks,
            'locale' => $source->locale,
            'locale_source_id' => $source->locale_source_id ?? $source->getKey(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        ActivityLogger::log('admin', 'create', $copy, ['duplicated_from' => $source->slug]);

        $this->redirectRoute('admin.pages.edit', ['page' => $copy->getKey()]);
    }

    public function unpublish(string $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);
        $this->authorize('publish', $page);

        DB::transaction(function () use ($page): void {
            $page->status = PageStatus::Archived;
            $page->save();
            $page->refresh();

            event(new PageUnpublished($page));
        });

        ActivityLogger::log('admin', 'unpublish', $page, ['slug' => $page->slug]);
        $this->dispatch('notify', message: '"'.$page->title.'" unpublished.');
    }

    public function publish(string $pageId, PublishGate $gate, RevisionManager $revisions): void
    {
        $page = Page::query()->findOrFail($pageId);
        $this->authorize('publish', $page);

        $inspection = $gate->inspect($page);

        if ($inspection['errors'] !== []) {
            $this->dispatch('notify', tone: 'danger', message: 'Publish blocked: '.implode(' ', array_values($inspection['errors'])));

            return;
        }

        DB::transaction(function () use ($page, $revisions): void {
            $page->status = PageStatus::Published;
            $page->published_at = $page->published_at ?? now();
            $page->updated_by = auth()->id();
            $page->save();
            $page->refresh();

            $revisions->record($page, (int) auth()->id());

            event(new PagePublished($page));
        });

        ActivityLogger::log('admin', 'publish', $page, ['slug' => $page->slug]);
        $this->dispatch('notify', tone: 'success', message: '"'.$page->title.'" published.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->q !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('title', 'like', "%{$this->q}%")
                    ->orWhere('slug', 'like', "%{$this->q}%");
            }))
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('cms.livewire.pages-table', [
            'pages' => $pages,
            'statuses' => PageStatus::options(),
            'types' => PageType::options(),
        ]);
    }
}
