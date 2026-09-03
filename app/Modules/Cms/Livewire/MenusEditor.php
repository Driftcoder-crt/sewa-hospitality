<?php

namespace App\Modules\Cms\Livewire;

use App\Modules\Cms\Enums\MenuItemType;
use App\Modules\Cms\Enums\MenuLocation;
use App\Modules\Cms\Models\Menu;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\Cms\Models\Page;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Menus admin (04-modules/01-cms.md §4.3): per-location item tree.
 * Reordering is keyboard-accessible (up/down, nest/unnest — the a11y
 * contract wins over drag-only UX). Edits go through a per-item draft
 * state (drafts[itemId]) saved explicitly — never half-typed URLs live
 * on the public site. Deleting a linked page flags items for review
 * (PageObserver); flagged items drop from the public render meanwhile.
 */
#[Layout('layouts.admin')]
class MenusEditor extends Component
{
    public string $location = 'header';

    /** Per-item edit drafts: itemId => ['label' =>, 'url' =>, 'target' =>]. */
    public array $drafts = [];

    public function updatedLocation(): void
    {
        $this->drafts = [];
    }

    public function addItem(): void
    {
        $this->authorize('updateAny', MenuItem::class);

        $menu = Menu::query()->where('location', $this->location)->firstOrFail();

        MenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'label' => 'New item',
            'type' => MenuItemType::Custom->value,
            'url' => '/',
            'target' => '_self',
            'sort' => (int) MenuItem::query()->where('menu_id', $menu->getKey())->max('sort') + 1,
        ]);

        ActivityLogger::log('admin', 'create', $menu, ['item' => 'New item']);
    }

    public function saveItem(string $itemId): void
    {
        $item = MenuItem::query()->findOrFail($itemId);
        $this->authorize('update', $item);

        $draft = $this->drafts[$itemId] ?? [];
        $label = trim((string) ($draft['label'] ?? $item->label));
        $url = trim((string) ($draft['url'] ?? (string) $item->url));
        $target = ($draft['target'] ?? '_self') === '_blank' ? '_blank' : '_self';

        if ($label === '') {
            $this->addError("drafts.{$itemId}.label", 'Label is required.');

            return;
        }

        $type = $item->type;
        if ($url !== '' && $url !== '#') {
            $type = str_starts_with($url, 'http') ? MenuItemType::Custom : $this->detectType($url);
        }

        $item->label = $label;
        $item->url = $url !== '' ? $url : null;
        $item->target = $target;
        $item->type = $type;
        $item->ref_id = $type === MenuItemType::Page ? $this->resolvePageId($url) : $item->ref_id;
        $item->flagged = false; // an explicit edit IS the review
        $item->save();

        unset($this->drafts[$itemId]);

        ActivityLogger::log('admin', 'update', $item, ['label' => $label, 'url' => $url]);
        $this->dispatch('notify', tone: 'success', message: 'Menu item saved.');
    }

    public function deleteItem(string $itemId): void
    {
        $item = MenuItem::query()->findOrFail($itemId);
        $this->authorize('delete', $item);

        // Children reattach to the item's parent — no orphan subtrees.
        MenuItem::query()->where('parent_id', $item->getKey())
            ->update(['parent_id' => $item->parent_id]);
        $item->delete();

        ActivityLogger::log('admin', 'delete', $item, ['label' => $item->label]);
    }

    public function move(string $itemId, string $direction): void
    {
        $item = MenuItem::query()->findOrFail($itemId);
        $this->authorize('update', $item);

        $siblings = MenuItem::query()
            ->where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get();

        $index = $siblings->search(fn (MenuItem $sibling): bool => $sibling->is($item));
        if ($index === false) {
            return;
        }

        $swapWith = match ($direction) {
            'up' => $index > 0 ? $siblings[$index - 1] : null,
            'down' => $index < $siblings->count() - 1 ? $siblings[$index + 1] : null,
            default => null,
        };

        if ($swapWith === null) {
            return;
        }

        $currentSort = $item->sort;
        $item->sort = $swapWith->sort;
        $swapWith->sort = $currentSort;
        $item->save();
        $swapWith->save();
    }

    public function nest(string $itemId): void
    {
        $item = MenuItem::query()->findOrFail($itemId);
        $this->authorize('update', $item);

        $previous = MenuItem::query()
            ->where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->where('sort', '<', $item->sort)
            ->orderByDesc('sort')
            ->first();

        if ($previous === null) {
            return;
        }

        $item->parent_id = $previous->getKey();
        $item->sort = (int) MenuItem::query()->where('parent_id', $previous->getKey())->max('sort') + 1;
        $item->save();
    }

    public function unnest(string $itemId): void
    {
        $item = MenuItem::query()->findOrFail($itemId);
        $this->authorize('update', $item);

        if ($item->parent_id === null) {
            return;
        }

        $parent = MenuItem::query()->find($item->parent_id);
        $item->parent_id = $parent?->parent_id;
        $item->sort = (int) MenuItem::query()->where('menu_id', $item->menu_id)->max('sort') + 1;
        $item->save();
    }

    public function render(): View
    {
        $this->authorize('updateAny', MenuItem::class);

        $menu = Menu::query()->where('location', $this->location)->first();

        $items = $menu
            ? MenuItem::query()
                ->where('menu_id', $menu->getKey())
                ->orderBy('sort')
                ->orderBy('created_at')
                ->get()
            : collect();

        // Seed drafts for untouched items so inputs stay controlled.
        foreach ($items as $item) {
            if (! isset($this->drafts[$item->getKey()])) {
                $this->drafts[$item->getKey()] = [
                    'label' => $item->label,
                    'url' => (string) $item->url,
                    'target' => $item->target,
                ];
            }
        }

        return view('cms.livewire.menus-editor', [
            'menu' => $menu,
            'topItems' => $items->whereNull('parent_id')->values(),
            'childrenOf' => fn ($parentId) => $items->where('parent_id', $parentId)->values(),
            'locations' => MenuLocation::options(),
        ]);
    }

    /** Future-safe type detection from the URL shape. */
    private function detectType(string $url): MenuItemType
    {
        if (str_starts_with($url, '/legal/') || str_starts_with($url, '/p/')) {
            return MenuItemType::Page;
        }

        return str_starts_with($url, '/') ? MenuItemType::Route : MenuItemType::Custom;
    }

    /** Page URLs written as /slug (and /legal|p forms) resolve to the page id. */
    private function resolvePageId(string $url): ?string
    {
        $slug = ltrim(trim($url), '/');
        $slug = preg_replace('#^(legal|p)/#', '', $slug) ?? $slug;

        return Page::query()->where('slug', $slug)->value('id');
    }
}
