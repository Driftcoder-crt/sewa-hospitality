<?php

namespace App\Modules\Cms\Livewire;

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Events\PagePublished;
use App\Modules\Cms\Events\PageUnpublished;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\Redirect;
use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Cms\Services\PublishGate;
use App\Modules\Cms\Services\RevisionManager;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Page editor (04-modules/01-cms.md §4.2): live block canvas + settings
 * drawer. Autosave every 10s while dirty (wire:poll); failure shows a
 * visible "unsaved changes — retry" banner, never silent loss (§6).
 * Publish runs the gate (SEO fields + noindex confirmation + render
 * probe); a published slug change offers the 301 redirect (§5).
 */
#[Layout('layouts.admin')]
class PageEditor extends Component
{
    public Page $page;

    // Settings drawer
    public string $title = '';

    public string $slug = '';

    public string $type = 'standard';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $canonical_override = '';

    public bool $noindex = false;

    public string $noindex_reason = '';

    public bool $noindex_confirmed = false;

    public string $scheduled_at = '';

    // Block canvas
    /** @var list<array{type: string, data: array<string, mixed>}> */
    public array $blocks = [];

    // 301 offer
    public bool $slugChanged = false;

    public bool $addRedirect = true;

    public string $originalSlug = '';

    // UX state: clean | dirty | saving | saved | error
    public string $autosaveState = 'clean';

    public string $autosaveError = '';

    /** @var array<string, string> publish-gate field errors */
    public array $gateErrors = [];

    /** @var array<string, string> per-block validation errors (block index keyed) */
    public array $blockErrors = [];

    public bool $showRevisions = false;

    /** Revision selected for diffing (id); empty = none. */
    public string $diffRevisionId = '';

    public function mount(Page $page): void
    {
        $this->authorize('update', $page);

        $this->fillFromPage($page);
        $this->originalSlug = $page->slug;
        $this->slugChanged = false;
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'slug') && $this->slug !== $this->originalSlug) {
            $this->slugChanged = $this->page->status === PageStatus::Published;
        }

        $this->autosaveState = 'dirty';
    }

    /** Autosave heartbeat — wire:poll every 10s while dirty. */
    public function autosave(RevisionManager $revisions): void
    {
        if ($this->autosaveState !== 'dirty') {
            return;
        }

        $this->saveDraft($revisions);
    }

    /** Explicit save (same path as autosave; used by the Save button). */
    public function save(RevisionManager $revisions): void
    {
        $this->saveDraft($revisions);
    }

    public function toggleRevisions(): void
    {
        $this->showRevisions = ! $this->showRevisions;
    }

    // ------------------------------------------------------------------
    // Blocks
    // ------------------------------------------------------------------

    public function addBlock(string $type): void
    {
        if (! BlockRegistry::has($type)) {
            return;
        }

        $defaults = [];
        foreach (BlockRegistry::definition($type)['fields'] as $name => $field) {
            $defaults[$name] = $field['default'] ?? match ($field['type']) {
                'items', 'ctas' => [],
                'boolean' => false,
                default => '',
            };
        }

        $this->blocks[] = ['type' => $type, 'data' => $defaults];
        $this->autosaveState = 'dirty';
    }

    public function removeBlock(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);
        $this->autosaveState = 'dirty';
    }

    public function moveBlockUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
        $this->blocks = array_values($this->blocks);
        $this->autosaveState = 'dirty';
    }

    public function moveBlockDown(int $index): void
    {
        if ($index >= count($this->blocks) - 1) {
            return;
        }

        [$this->blocks[$index + 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index + 1]];
        $this->blocks = array_values($this->blocks);
        $this->autosaveState = 'dirty';
    }

    // Repeatable item helpers (items / ctas fields)
    public function addItem(int $blockIndex, string $field): void
    {
        $this->blocks[$blockIndex]['data'][$field][] = [];
        $this->autosaveState = 'dirty';
    }

    public function removeItem(int $blockIndex, string $field, int $itemIndex): void
    {
        unset($this->blocks[$blockIndex]['data'][$field][$itemIndex]);
        $this->blocks[$blockIndex]['data'][$field] = array_values($this->blocks[$blockIndex]['data'][$field]);
        $this->autosaveState = 'dirty';
    }

    // ------------------------------------------------------------------
    // Publishing
    // ------------------------------------------------------------------

    public function publish(PublishGate $gate, RevisionManager $revisions): void
    {
        $this->authorize('publish', $this->page);

        $page = $this->applyToPage(new Page, persist: false);
        $inspection = $gate->inspect($page);

        if ($inspection['errors'] !== []) {
            $this->gateErrors = $inspection['errors'];

            return;
        }

        $this->gateErrors = [];

        $wasPublished = $this->page->status === PageStatus::Published;

        DB::transaction(function () use ($revisions): void {
            $this->applyToPage($this->page, persist: true);
            $this->page->status = PageStatus::Published;
            $this->page->published_at = $this->page->published_at ?? now();
            $this->page->updated_by = auth()->id();
            $this->handleSlugMove();
            $this->page->save();
            $this->page->refresh();

            $revisions->record($this->page, (int) auth()->id());

            if ($this->page->status === PageStatus::Published) {
                event(new PagePublished($this->page));
            }
        });

        ActivityLogger::log('admin', 'publish', $this->page, ['slug' => $this->page->slug]);
        $this->fillFromPage($this->page);
        $this->autosaveState = 'saved';

        $this->dispatch('notify', tone: 'success', message: $wasPublished
            ? 'Page updated and live.'
            : 'Page published.');
    }

    public function unpublish(): void
    {
        $this->authorize('publish', $this->page);

        $this->page->status = PageStatus::Archived;
        $this->page->updated_by = auth()->id();
        $this->page->save();
        $this->page->refresh();

        event(new PageUnpublished($this->page));
        ActivityLogger::log('admin', 'unpublish', $this->page, ['slug' => $this->page->slug]);

        $this->fillFromPage($this->page);
        $this->dispatch('notify', tone: 'success', message: 'Page unpublished.');
    }

    // ------------------------------------------------------------------
    // Revisions
    // ------------------------------------------------------------------

    public function restoreRevision(string $revisionId, RevisionManager $revisions): void
    {
        $this->authorize('update', $this->page);

        $revision = $this->page->revisions()->findOrFail($revisionId);
        $restored = $revisions->restore($revision, (int) auth()->id());

        ActivityLogger::log('admin', 'update', $restored, ['restored_revision' => $revisionId]);
        $this->fillFromPage($restored);
        $this->originalSlug = $restored->slug;
        $this->slugChanged = false;
        $this->autosaveState = 'saved';
        $this->diffRevisionId = '';

        $this->dispatch('notify', tone: 'success', message: 'Revision restored (a new revision was recorded).');
    }

    // ------------------------------------------------------------------

    public function render(): View
    {
        $revisions = $this->showRevisions
            ? $this->page->revisions()->take(RevisionManager::CAP)->get()
            : collect();

        $diff = null;
        if ($this->showRevisions && $this->diffRevisionId !== '') {
            $revision = $this->page->revisions()->find($this->diffRevisionId);
            if ($revision) {
                $current = [
                    'title' => $this->title,
                    'slug' => $this->slug,
                    'meta_title' => $this->meta_title,
                    'meta_description' => $this->meta_description,
                    'noindex' => $this->noindex,
                    'blocks' => $this->blocks,
                ];
                $diff = app(RevisionManager::class)->diff($revision->snapshot ?? [], $current);
            }
        }

        return view('cms.livewire.page-editor', [
            'registry' => BlockRegistry::grouped(),
            'definitions' => BlockRegistry::all(),
            'revisions' => $revisions,
            'diff' => $diff,
            'types' => PageType::options(),
            'statuses' => PageStatus::options(),
            'previewUrl' => route('admin.pages.preview', ['page' => $this->page->getKey()]),
        ]);
    }

    // ------------------------------------------------------------------

    private function saveDraft(RevisionManager $revisions): void
    {
        $this->autosaveState = 'saving';

        try {
            $this->authorize('update', $this->page);

            $validated = $this->validateDraft();
            if ($validated !== []) {
                $this->autosaveState = 'error';
                $this->autosaveError = implode(' ', $validated);

                return;
            }

            DB::transaction(function () use ($revisions): void {
                $this->applyToPage($this->page, persist: true);
                $this->page->updated_by = auth()->id();
                $this->handleSlugMove();
                $this->page->save();
                $this->page->refresh();

                $revisions->record($this->page, (int) auth()->id());
            });

            $this->originalSlug = $this->page->slug;
            $this->slugChanged = false;
            $this->autosaveState = 'saved';
            $this->autosaveError = '';
        } catch (Throwable $e) {
            $this->autosaveState = 'error';
            $this->autosaveError = 'Saving failed — your changes are still in the editor. Retry in a moment.';
        }
    }

    /** @return list<string> hard validation errors (draft still saved otherwise) */
    private function validateDraft(): array
    {
        $errors = [];

        $slug = mb_strtolower(trim($this->slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors[] = 'Slug must be lowercase letters, numbers and single hyphens.';
        } elseif (Page::isReservedSlug($slug) && $slug !== $this->originalSlug) {
            $errors[] = '"'.$slug.'" is a reserved system path.';
        } else {
            $collision = Page::query()
                ->where('slug', $slug)
                ->whereKeyNot($this->page->getKey())
                ->exists();
            if ($collision) {
                $errors[] = 'That slug is already in use — slug collisions are auto-301 territory, pick a unique one.';
            }
        }

        if (trim($this->title) === '') {
            $errors[] = 'Title is required.';
        }

        return $errors;
    }

    /** Published slug change → create the 301 (auto-offer honored). */
    private function handleSlugMove(): void
    {
        if (! $this->slugChanged
            || $this->slug === $this->originalSlug
            || $this->page->status !== PageStatus::Published
            || ! $this->addRedirect) {
            return;
        }

        Redirect::query()->firstOrCreate(
            ['from' => $this->originalSlug],
            [
                'to' => '/'.$this->slug,
                'code' => 301,
                'note' => 'Slug move of page "'.$this->title.'" (auto)',
                'active' => true,
            ],
        );

        ActivityLogger::log('admin', 'create', null, [
            'redirect' => $this->originalSlug.' → /'.$this->slug,
            'reason' => 'published slug change',
        ]);
    }

    /** Copy editor state onto a page model (persist=false → detached). */
    private function applyToPage(Page $page, bool $persist): Page
    {
        $page->title = trim($this->title);
        $page->slug = mb_strtolower(trim($this->slug));
        $page->type = PageType::from($this->type);
        $page->meta_title = trim($this->meta_title);
        $page->meta_description = trim($this->meta_description);
        $page->canonical_override = trim($this->canonical_override) ?: null;
        $page->blocks = $this->blocks;

        // Noindex confirmation (04-modules/01-cms.md §5: typed confirm).
        $page->noindex = $this->noindex;
        $page->noindex_reason = $this->noindex ? trim($this->noindex_reason) : null;
        if ($this->noindex && $this->noindex_confirmed && $page->noindex_confirmed_at === null) {
            $page->noindex_confirmed_at = now();
            $page->noindex_confirmed_by = auth()->id();
        }
        if (! $this->noindex) {
            $page->noindex_confirmed_at = null;
            $page->noindex_confirmed_by = null;
        }

        $page->scheduled_at = $this->scheduled_at !== '' ? Carbon::parse($this->scheduled_at) : null;

        if ($persist && $page->status === PageStatus::Draft && $page->scheduled_at && $page->scheduled_at->isFuture()) {
            $page->status = PageStatus::Scheduled;
        }

        return $page;
    }

    private function fillFromPage(Page $page): void
    {
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->type = $page->type->value;
        $this->meta_title = (string) $page->meta_title;
        $this->meta_description = (string) $page->meta_description;
        $this->canonical_override = (string) $page->canonical_override;
        $this->noindex = $page->noindex;
        $this->noindex_reason = (string) $page->noindex_reason;
        $this->noindex_confirmed = $page->noindex_confirmed_at !== null;
        $this->scheduled_at = $page->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->blocks = $page->blockList();
    }
}
