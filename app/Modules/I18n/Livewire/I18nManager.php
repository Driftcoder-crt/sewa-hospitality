<?php

namespace App\Modules\I18n\Livewire;

use App\Modules\Ai\Jobs\TranslateContent;
use App\Modules\Ai\Services\TranslatableFields;
use App\Modules\Blog\Models\Post;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Models\Page;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\I18n\Enums\TranslationStatus;
use App\Modules\I18n\Enums\UiNamespace;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Models\Translation;
use App\Modules\Services\Models\Service;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * I18n admin (04-modules/11-multilingual.md §6): locales management,
 * the UI-string review queue (machine → human with reviewer
 * attribution) and the content translation queue (machine-draft
 * variants side-by-side with their EN source — approve to open the
 * entity editor, requeue, or discard).
 */
#[Layout('layouts.admin')]
final class I18nManager extends Component
{
    use WithPagination;

    public string $tab = 'locales';

    // UI strings filters
    public string $stringLocale = 'ja';

    public string $stringNamespace = 'site';

    public string $stringStatus = '';

    // Edit-and-approve working state
    public ?string $editingStringId = null;

    public string $editingValue = '';

    // Manual translation dispatch
    public string $manualEntity = Page::class;

    public string $manualId = '';

    public function switchTab(string $tab): void
    {
        $this->tab = in_array($tab, ['locales', 'strings', 'content'], true) ? $tab : 'locales';
    }

    /* ── Locales ───────────────────────────────────────────────────── */

    public function toggleLocale(string $code): void
    {
        $this->authorize('i18n.manage');

        $locale = Locale::query()->findOrFail($code);

        if ($locale->isDefault()) {
            $this->dispatch('notify', tone: 'error', message: 'EN is the x-default and cannot be disabled.');

            return;
        }

        $locale->enabled = ! $locale->enabled;
        $locale->save();

        ActivityLogger::log('admin', 'update', $locale, ['enabled' => $locale->enabled]);
        $this->dispatch('notify', tone: 'success', message: "Locale {$code} ".($locale->enabled ? 'enabled' : 'disabled').'.');
    }

    public function toggleAutoTranslate(string $code): void
    {
        $this->authorize('i18n.manage');

        $locale = Locale::query()->findOrFail($code);

        if ($locale->isDefault()) {
            $this->dispatch('notify', tone: 'error', message: 'EN is the translation source and never auto-translates.');

            return;
        }

        $locale->auto_translate = ! $locale->auto_translate;
        $locale->save();

        ActivityLogger::log('admin', 'update', $locale, ['auto_translate' => $locale->auto_translate]);
    }

    /* ── UI strings review (11-multilingual §6.2) ──────────────────── */

    public function editString(string $id): void
    {
        $this->authorize('i18n.manage');

        $string = Translation::query()->findOrFail($id);
        $this->editingStringId = $id;
        $this->editingValue = (string) $string->value;
    }

    public function approveEdited(): void
    {
        $this->authorize('i18n.manage');

        $string = Translation::query()->findOrFail((string) $this->editingStringId);
        $this->validate(['editingValue' => 'required|string|max:5000']);

        $string->approveWith(request()->user(), $this->editingValue);
        ActivityLogger::log('admin', 'update', $string, ['status' => 'human-reviewed', 'key' => $string->key]);

        $this->cancelEdit();
        $this->dispatch('notify', tone: 'success', message: 'String approved.');
    }

    public function approve(string $id): void
    {
        $this->authorize('i18n.manage');

        $string = Translation::query()->findOrFail($id);
        $string->approve(request()->user());

        ActivityLogger::log('admin', 'update', $string, ['status' => 'human-reviewed', 'key' => $string->key]);
        $this->dispatch('notify', tone: 'success', message: 'String approved.');
    }

    public function reject(string $id): void
    {
        $this->authorize('i18n.manage');

        $string = Translation::query()->findOrFail($id);
        $string->reject(request()->user());

        ActivityLogger::log('admin', 'update', $string, ['status' => 'rejected to machine', 'key' => $string->key]);
        $this->dispatch('notify', tone: 'success', message: 'String returned to machine draft.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingStringId', 'editingValue']);
    }

    /* ── Content translation queue (machine-draft variants) ────────── */

    /** Requeue machine translation for a draft variant's EN source. */
    public function requeue(string $entityClass, string $sourceId, string $locale): void
    {
        $this->authorize('i18n.manage');

        if (TranslatableFields::for($entityClass) === null || ! Locale::isEnabled($locale)) {
            $this->dispatch('notify', tone: 'error', message: 'Unsupported translation target.');

            return;
        }

        TranslateContent::dispatch($entityClass, $sourceId, $locale);
        ActivityLogger::log('admin', 'create', null, ['i18n.requeue' => $entityClass.':'.$sourceId.':'.$locale]);
        $this->dispatch('notify', tone: 'success', message: 'Translation requeued (queue: ai).');
    }

    public function discard(string $entityClass, string $variantId): void
    {
        $this->authorize('i18n.manage');

        if (TranslatableFields::for($entityClass) === null) {
            return;
        }

        $variant = $entityClass::query()->findOrFail($variantId);

        if ($variant->status !== 'draft') {
            $this->dispatch('notify', tone: 'error', message: 'Only draft variants can be discarded.');

            return;
        }

        ActivityLogger::log('admin', 'delete', $variant, ['i18n.discard' => $variant->locale]);
        $variant->delete();

        $this->dispatch('notify', tone: 'success', message: 'Machine draft discarded.');
    }

    public function dispatchManual(): void
    {
        $this->authorize('i18n.manage');

        $this->validate([
            'manualEntity' => 'required|string',
            'manualId' => 'required|string|max:40',
        ]);

        if (TranslatableFields::for($this->manualEntity) === null) {
            $this->addError('manualEntity', 'Unsupported entity type.');

            return;
        }

        $source = $this->manualEntity::query()->find($this->manualId);

        if ($source === null) {
            $this->addError('manualId', 'No entity found for that id.');

            return;
        }

        $queued = 0;

        foreach (Locale::query()->enabled()->translatable()->where('code', '!=', $source->locale)->pluck('code') as $code) {
            TranslateContent::dispatch($this->manualEntity, (string) $source->getKey(), (string) $code);
            $queued++;
        }

        ActivityLogger::log('admin', 'create', $source, ['i18n.manual_dispatch' => $queued.' locales']);
        $this->reset('manualId');
        $this->dispatch('notify', tone: 'success', message: "Queued {$queued} translation job(s).");
    }

    /* ── Render ────────────────────────────────────────────────────── */

    public function render(): View
    {
        $this->authorize('i18n.manage');

        $strings = Translation::query()
            ->with('reviewer:id,name')
            ->forLocale($this->stringLocale)
            ->inNamespace($this->stringNamespace)
            ->when($this->stringStatus !== '', fn ($q) => $q->where('status', $this->stringStatus))
            ->orderBy('key')
            ->paginate(20, ['*'], 'stringsPage');

        // Machine-draft content variants across the supported set —
        // draft status IS the machine gate (TranslateContent docblock).
        $drafts = collect();

        foreach ([
            Page::class,
            Service::class,
            City::class,
            Post::class,
            CsrStory::class,
        ] as $entityClass) {
            $entityClass::query()
                ->whereNotNull('locale_source_id')
                ->where('status', 'draft')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
                ->each(fn ($variant) => $drafts->push([
                    'class' => $entityClass,
                    'label' => class_basename($entityClass),
                    'variant' => $variant,
                    'source' => $entityClass::query()->find($variant->locale_source_id),
                ]));
        }

        $drafts = $drafts->sortByDesc(fn (array $row): ?string => $row['variant']?->updated_at)->values();

        return view('i18n.livewire.i18n-manager', [
            'locales' => Locale::query()->orderBy('code')->get(),
            'switcherLocales' => Locale::query()->enabled()->orderBy('code')->pluck('native_name', 'code'),
            'strings' => $strings,
            'statuses' => TranslationStatus::options(),
            'namespaces' => UiNamespace::options(),
            'drafts' => $drafts->take(15),
            'entityOptions' => [
                Page::class => 'Page',
                Service::class => 'Service',
                City::class => 'City',
                Post::class => 'Post',
                CsrStory::class => 'CSR story',
            ],
        ]);
    }
}
