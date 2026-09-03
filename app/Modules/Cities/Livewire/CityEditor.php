<?php

namespace App\Modules\Cities\Livewire;

use App\Modules\Cities\Enums\CityStatus;
use App\Modules\Cities\Events\CityPublished;
use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Services\Models\Service;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Cities admin (04-modules/10-cities-content.md §4.1): CRUD + block
 * canvas + SEO drawer + hub flag + coverage editor (services × city
 * with local notes). Publish runs a gate: meta + description + blocks.
 */
#[Layout('layouts.admin')]
class CityEditor extends Component
{
    public City $city;

    public string $name = '';

    public string $slug = '';

    public string $state = '';

    public string $description = '';

    public bool $is_hub = false;

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $noindex = false;

    public string $noindex_reason = '';

    public bool $noindex_confirmed = false;

    /** @var array<string, string> serviceId => note ('' = covered generically) */
    public array $coverage = [];

    /** @var array<int|string, mixed> */
    public array $blocks = [];

    public string $autosaveState = 'clean';

    public string $autosaveError = '';

    public function mount(City $city): void
    {
        $this->authorize('update', $city);
        $this->fillFromCity($city);
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
            $this->authorize('update', $this->city);

            $this->applyToCity();
            $this->city->updated_by = auth()->id();
            $this->city->save();
            $this->city->refresh();

            $this->city->services()->sync(collect($this->coverage)
                ->filter(fn ($note, $serviceId): bool => (bool) $serviceId)
                ->mapWithKeys(fn ($note, $serviceId): array => [$serviceId => ['note' => trim((string) $note) ?: null]])
                ->all());

            ActivityLogger::log('admin', 'update', $this->city, ['name' => $this->name]);
            $this->autosaveState = 'saved';
        } catch (Throwable $e) {
            $this->autosaveState = 'error';
            $this->autosaveError = 'Saving failed — your changes are still in the editor. Retry in a moment.';
        }
    }

    public function publish(): void
    {
        $this->authorize('publish', $this->city);
        $this->save();

        $errors = [];
        if (trim($this->meta_title) === '') {
            $errors['meta_title'] = 'Meta title is required.';
        }
        if (trim($this->meta_description) === '') {
            $errors['meta_description'] = 'Meta description is required.';
        }
        if ($this->city->description === null || trim((string) $this->city->description) === '') {
            $errors['description'] = 'City intro is required.';
        }

        if ($errors !== []) {
            $this->dispatch('notify', tone: 'danger', message: 'Publish blocked: '.implode(' ', $errors));

            return;
        }

        DB::transaction(function (): void {
            $this->city->status = CityStatus::Published;
            $this->city->save();
            $this->city->refresh();

            event(new CityPublished($this->city));
        });

        ActivityLogger::log('admin', 'publish', $this->city, ['slug' => $this->city->slug]);
        $this->dispatch('notify', tone: 'success', message: '"'.$this->city->name.'" published.');
    }

    public function unpublish(): void
    {
        $this->authorize('publish', $this->city);

        $this->city->status = CityStatus::Archived;
        $this->city->save();

        ActivityLogger::log('admin', 'unpublish', $this->city, ['slug' => $this->city->slug]);
        $this->dispatch('notify', tone: 'success', message: 'City unpublished.');
    }

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

    public function render(): View
    {
        $this->authorize('viewAny', City::class);

        return view('cities.livewire.city-editor', [
            'definitions' => BlockRegistry::grouped(),
            'allServices' => Service::query()
                ->orderBy('parent_id')
                ->orderBy('sort')
                ->get(['id', 'name', 'parent_id']),
            'units' => HousingUnit::query()->where('city_id', $this->city->getKey())->count(),
        ]);
    }

    private function applyToCity(): void
    {
        $this->city->name = trim($this->name);
        $this->city->slug = mb_strtolower(trim($this->slug));
        $this->city->state = trim($this->state);
        $this->city->description = trim($this->description);
        $this->city->is_hub = $this->is_hub;
        $this->city->meta_title = trim($this->meta_title);
        $this->city->meta_description = trim($this->meta_description);
        $this->city->content_blocks = $this->blocks;
        $this->city->noindex = $this->noindex;
        $this->city->noindex_reason = $this->noindex ? trim($this->noindex_reason) : null;
        if ($this->noindex && $this->noindex_confirmed && $this->city->noindex_confirmed_at === null) {
            $this->city->noindex_confirmed_at = now();
            $this->city->noindex_confirmed_by = auth()->id();
        }
        if (! $this->noindex) {
            $this->city->noindex_confirmed_at = null;
            $this->city->noindex_confirmed_by = null;
        }
    }

    private function fillFromCity(City $city): void
    {
        $this->name = $city->name;
        $this->slug = $city->slug;
        $this->state = $city->state;
        $this->description = (string) $city->description;
        $this->is_hub = $city->is_hub;
        $this->meta_title = (string) $city->meta_title;
        $this->meta_description = (string) $city->meta_description;
        $this->noindex = $city->noindex;
        $this->noindex_reason = (string) $city->noindex_reason;
        $this->noindex_confirmed = $city->noindex_confirmed_at !== null;
        $this->coverage = $city->services()->pluck('note', 'services.id')->all();
        $this->blocks = is_array($city->content_blocks) ? array_values($city->content_blocks) : [];
    }
}
