<div class="admin-screen">
@section('title', 'Menus — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Menus</h1>
            <p class="eyebrow mt-1 text-ink-muted">Content · navigation trees</p>
        </div>
        <button wire:click="addItem" type="button"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            Add item
        </button>
    </div>

    <div class="mt-4 flex gap-2" role="tablist" aria-label="Menu locations">
        @foreach ($locations as $value => $label)
            <button type="button" wire:click="$set('location', '{{ $value }}')" role="tab"
                    aria-selected="{{ $location === $value ? 'true' : 'false' }}"
                    class="inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-semibold {{ $location === $value ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($menu === null)
        <div class="mt-5 rounded-xl border border-dashed border-line p-8 text-center">
            <p class="font-display text-lg text-ink">No menu for this location yet</p>
            <p class="mt-1 text-sm text-ink-soft">Run the CmsSeeder or ask an admin to create a "{{ ucfirst($location) }}" menu.</p>
        </div>
    @else
        <div class="mt-5 flex flex-col gap-3">
            @forelse ($topItems as $item)
                @php($draft = $drafts[$item->getKey()] ?? ['label' => $item->label, 'url' => (string) $item->url, 'target' => $item->target])
                <article class="rounded-xl border {{ $item->flagged ? 'border-danger-500/40' : 'border-line' }} bg-paper-2 p-4">
                    <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <label class="block text-sm">
                            <span class="text-xs font-semibold text-ink-soft">Label</span>
                            <input type="text" wire:model.live.debounce.300ms="drafts.{{ $item->getKey() }}.label"
                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            @error("drafts.{$item->getKey()}.label") <span class="mt-1 block text-xs text-danger-500">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="text-xs font-semibold text-ink-soft">URL (page slug path, e.g. /about)</span>
                            <input type="text" wire:model.live.debounce.300ms="drafts.{{ $item->getKey() }}.url"
                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <span class="mt-1 block text-xs text-ink-muted">resolves to <code>{{ $item->href() ?? '—' }}</code></span>
                        </label>
                        <div class="flex items-end gap-1">
                            <button type="button" wire:click="nest('{{ $item->getKey() }}')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Nest under previous item" title="Nest under previous">↳</button>
                            <button type="button" wire:click="move('{{ $item->getKey() }}', 'up')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move up">↑</button>
                            <button type="button" wire:click="move('{{ $item->getKey() }}', 'down')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move down">↓</button>
                            <button type="button" wire:click="deleteItem('{{ $item->getKey() }}')" wire:confirm="Delete this menu item? Children reattach to its parent."
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-danger-500/10" aria-label="Delete item">✕</button>
                            <button type="button" wire:click="saveItem('{{ $item->getKey() }}')"
                                    class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">Save</button>
                        </div>
                    </div>

                    @if ($item->flagged)
                        <p class="mt-3 rounded-lg border border-danger-500/40 bg-danger-500/10 p-3 text-sm text-ink" role="status">
                            The page this item pointed to was deleted. Fix the URL and Save — the item is hidden on the public site until then.
                        </p>
                    @endif
                </article>

                @foreach ($childrenOf($item->getKey()) as $child)
                    @php($childDraft = $drafts[$child->getKey()] ?? ['label' => $child->label, 'url' => (string) $child->url, 'target' => $child->target])
                    <article class="ms-4 rounded-xl border {{ $child->flagged ? 'border-danger-500/40' : 'border-line' }} bg-paper p-4 sm:ms-8">
                        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            <label class="block text-sm">
                                <span class="text-xs font-semibold text-ink-soft"><span aria-hidden="true">↳</span> Child label</span>
                                <input type="text" wire:model.live.debounce.300ms="drafts.{{ $child->getKey() }}.label"
                                       class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            </label>
                            <label class="block text-sm">
                                <span class="text-xs font-semibold text-ink-soft">URL</span>
                                <input type="text" wire:model.live.debounce.300ms="drafts.{{ $child->getKey() }}.url"
                                       class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                <span class="mt-1 block text-xs text-ink-muted">resolves to <code>{{ $child->href() ?? '—' }}</code></span>
                            </label>
                            <div class="flex items-end gap-1">
                                <button type="button" wire:click="unnest('{{ $child->getKey() }}')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Unnest" title="Unnest">↰</button>
                                <button type="button" wire:click="move('{{ $child->getKey() }}', 'up')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move up">↑</button>
                                <button type="button" wire:click="move('{{ $child->getKey() }}', 'down')" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move down">↓</button>
                                <button type="button" wire:click="deleteItem('{{ $child->getKey() }}')" wire:confirm="Delete this menu item?"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink-soft hover:bg-danger-500/10" aria-label="Delete item">✕</button>
                                <button type="button" wire:click="saveItem('{{ $child->getKey() }}')"
                                        class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">Save</button>
                            </div>
                        </div>
                        @if ($child->flagged)
                            <p class="mt-3 rounded-lg border border-danger-500/40 bg-danger-500/10 p-3 text-sm text-ink" role="status">Flagged — its target page was deleted.</p>
                        @endif
                    </article>
                @endforeach
            @empty
                <div class="rounded-xl border border-dashed border-line p-8 text-center">
                    <p class="font-display text-lg text-ink">Empty menu</p>
                    <p class="mt-1 text-sm text-ink-soft">Add an item to start the tree.</p>
                </div>
            @endforelse
        </div>

        <p class="mt-3 text-xs text-ink-muted">Keyboard-first: every reorder control is a real button (44 px target). Deleting a page auto-flags items pointing at it — they are hidden on the public site until reviewed here.</p>
    @endif
</div>
