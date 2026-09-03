<?php

namespace App\Modules\Services\Livewire;

use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Services\Enums\ServiceFamily;
use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Events\ServicePublished;
use App\Modules\Services\Events\ServiceUpdated;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Services\ServicePublishGate;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Service editor (04-modules/02-services-module.md §4.2): the CMS
 * block canvas + a service-specific drawer — family/parent, icon key,
 * lead_tag (admin+ only), FAQ repeater (renders FAQPage schema),
 * related services picker, SEO panel, noindex confirm. Publish runs
 * the ServicePublishGate; slug changes create the 301 like CMS.
 */
#[Layout('layouts.admin')]
class ServiceEditor extends Component
{
    public Service $service;

    public string $name = '';

    public string $slug = '';

    public string $family = 'employee-mobility';

    public string $parent_id = '';

    public string $short_desc = '';

    public string $intro = '';

    public string $icon_svg_key = '';

    public string $lead_tag = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $noindex = false;

    public string $noindex_reason = '';

    public bool $noindex_confirmed = false;

    public string $cta_label_override = '';

    /** @var list<array{q: string, a: string}> */
    public array $faq = [];

    /** @var list<string> related service ids */
    public array $related_ids = [];

    /** @var list<array{type: string, data: array<string, mixed>}> */
    public array $blocks = [];

    public string $autosaveState = 'clean';

    public string $autosaveError = '';

    /** @var array<string, string> */
    public array $gateErrors = [];

    public function mount(Service $service): void
    {
        $this->authorize('update', $service);
        $this->fillFromService($service);
    }

    public function updated($property): void
    {
        $this->autosaveState = 'dirty';
    }

    public function autosave(): void
    {
        if ($this->autosaveState === 'dirty') {
            $this->save();
        }
    }

    public function save(): void
    {
        $this->autosaveState = 'saving';

        try {
            $this->authorize('update', $this->service);

            $errors = $this->validateDraft();
            if ($errors !== []) {
                $this->autosaveState = 'error';
                $this->autosaveError = implode(' ', $errors);

                return;
            }

            $wasPublished = $this->service->status === ServiceStatus::Published;

            DB::transaction(function (): void {
                $this->applyToService();
                $this->service->updated_by = auth()->id();
                $this->service->save();
                $this->service->refresh();

                $this->service->related()->sync($this->related_ids);

                if ($wasPublished) {
                    event(new ServiceUpdated($this->service));
                }
            });

            ActivityLogger::log('admin', 'update', $this->service, ['name' => $this->name]);
            $this->autosaveState = 'saved';
        } catch (\Throwable $e) {
            $this->autosaveState = 'error';
            $this->autosaveError = 'Saving failed — your changes are still in the editor. Retry in a moment.';
        }
    }

    public function publish(ServicePublishGate $gate): void
    {
        $this->authorize('publish', $this->service);

        $this->save();

        $inspection = $gate->inspect($this->service->refresh());
        if ($inspection['errors'] !== []) {
            $this->gateErrors = $inspection['errors'];
            $this->dispatch('notify', tone: 'danger', message: 'Publish blocked — fix the listed fields.');

            return;
        }

        $this->gateErrors = [];

        DB::transaction(function (): void {
            $this->service->status = ServiceStatus::Published;
            $this->service->save();
            $this->service->refresh();

            event(new ServicePublished($this->service));
        });

        ActivityLogger::log('admin', 'publish', $this->service, ['slug' => $this->service->slug]);
        $this->dispatch('notify', tone: 'success', message: '"'.$this->service->name.'" published.');
    }

    public function unpublish(): void
    {
        $this->authorize('publish', $this->service);

        $this->service->status = ServiceStatus::Archived;
        $this->service->save();

        ActivityLogger::log('admin', 'unpublish', $this->service, ['slug' => $this->service->slug]);
        $this->dispatch('notify', tone: 'success', message: 'Service archived (leads keep their tag history).');
    }

    // Blocks -----------------------------------------------------------

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

    public function addFaqItem(): void
    {
        $this->faq[] = ['q' => '', 'a' => ''];
        $this->autosaveState = 'dirty';
    }

    public function removeFaqItem(int $index): void
    {
        unset($this->faq[$index]);
        $this->faq = array_values($this->faq);
        $this->autosaveState = 'dirty';
    }

    public function render(): View
    {
        $this->authorize('viewAny', Service::class);

        return view('services.livewire.service-editor', [
            'registry' => BlockRegistry::grouped(),
            'definitions' => BlockRegistry::all(),
            'families' => ServiceFamily::options(),
            'parents' => Service::query()
                ->whereNull('parent_id')
                ->whereKeyNot($this->service->getKey())
                ->orderBy('name')
                ->get(['id', 'name']),
            'relatedOptions' => Service::query()
                ->whereKeyNot($this->service->getKey())
                ->orderBy('name')
                ->get(['id', 'name']),
            'canPublish' => auth()->user()->can('publish', $this->service),
            'canLeadTag' => auth()->user()->hasAnyRole(['super-admin', 'admin']),
        ]);
    }

    // ------------------------------------------------------------------

    /** @return list<string> */
    private function validateDraft(): array
    {
        $errors = [];

        $slug = mb_strtolower(trim($this->slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors[] = 'Slug must be lowercase letters, numbers and single hyphens.';
        } elseif ($slug !== $this->service->slug
            && Service::query()->where('slug', $slug)->whereKeyNot($this->service->getKey())->exists()) {
            $errors[] = 'That slug is already in use (catalog slugs are locked to 03-service-catalog.md — 301s apply on change).';
        }

        if (trim($this->name) === '') {
            $errors[] = 'Name is required.';
        }

        if (trim($this->lead_tag) === '') {
            $errors[] = 'Lead tag is required (every form on the page carries it into Leads).';
        }

        return $errors;
    }

    private function applyToService(): void
    {
        $this->service->name = trim($this->name);
        $this->service->slug = mb_strtolower(trim($this->slug));
        $this->service->family = ServiceFamily::from($this->family);
        $this->service->parent_id = $this->parent_id !== '' ? $this->parent_id : null;
        $this->service->short_desc = trim($this->short_desc);
        $this->service->intro = trim($this->intro);
        $this->service->icon_svg_key = trim($this->icon_svg_key) ?: null;
        $this->service->meta_title = trim($this->meta_title);
        $this->service->meta_description = trim($this->meta_description);
        $this->service->cta_label_override = trim($this->cta_label_override) ?: null;
        $this->service->content_blocks = $this->blocks;
        $this->service->faq = collect($this->faq)
            ->filter(fn (array $item): bool => trim((string) ($item['q'] ?? '')) !== '')
            ->values()
            ->all();

        $this->service->noindex = $this->noindex;
        $this->service->noindex_reason = $this->noindex ? trim($this->noindex_reason) : null;
        if ($this->noindex && $this->noindex_confirmed && $this->service->noindex_confirmed_at === null) {
            $this->service->noindex_confirmed_at = now();
            $this->service->noindex_confirmed_by = auth()->id();
        }
        if (! $this->noindex) {
            $this->service->noindex_confirmed_at = null;
            $this->service->noindex_confirmed_by = null;
        }

        // lead_tag is admin+ — silently keep the current value otherwise.
        if (auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            $this->service->lead_tag = mb_strtolower(trim($this->lead_tag));
        }

        // Cycle guard: a service can never be its own ancestor.
        if ($this->service->parent_id !== null) {
            $ancestor = $this->service->parent_id;
            $depth = 0;
            while ($ancestor !== null && $depth < 10) {
                if ($ancestor === $this->service->getKey()) {
                    $this->service->parent_id = null;

                    break;
                }
                $ancestor = Service::query()->whereKey($ancestor)->value('parent_id');
                $depth++;
            }
        }
    }

    private function fillFromService(Service $service): void
    {
        $this->name = $service->name;
        $this->slug = $service->slug;
        $this->family = $service->family->value;
        $this->parent_id = (string) $service->parent_id;
        $this->short_desc = (string) $service->short_desc;
        $this->intro = (string) $service->intro;
        $this->icon_svg_key = (string) $service->icon_svg_key;
        $this->lead_tag = $service->lead_tag;
        $this->meta_title = (string) $service->meta_title;
        $this->meta_description = (string) $service->meta_description;
        $this->noindex = $service->noindex;
        $this->noindex_reason = (string) $service->noindex_reason;
        $this->noindex_confirmed = $service->noindex_confirmed_at !== null;
        $this->cta_label_override = (string) $service->cta_label_override;
        $this->faq = $service->faq ?? [];
        $this->related_ids = $service->related()->pluck('services.id')->all();
        $this->blocks = is_array($service->content_blocks) ? array_values($service->content_blocks) : [];
    }
}
