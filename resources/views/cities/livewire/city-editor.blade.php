<div class="admin-screen">
@section('title', 'Edit city — Sewa Admin')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">{{ $city->name }}</h1>
            <p class="eyebrow mt-1 text-ink-muted">Cities · /cities/{{ $city->slug }} · {{ $city->status->label() }} · {{ $units }} housing unit(s)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="save" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink hover:bg-paper-3">Save</button>
            @if ($city->status->isPublic())
                <button type="button" wire:click="unpublish" wire:confirm="Unpublish this city?" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Unpublish</button>
            @else
                <button type="button" wire:click="publish" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">Publish</button>
            @endif
        </div>
    </div>

    <div class="mt-3" aria-live="polite">
        @if ($autosaveState === 'error')
            <div class="flex items-center justify-between gap-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4 text-sm">
                <span class="text-ink">{{ $autosaveError }}</span>
                <button type="button" wire:click="save" class="inline-flex min-h-[36px] items-center rounded-lg border border-line bg-paper px-3 text-xs font-semibold">Retry now</button>
            </div>
        @elseif ($autosaveState === 'dirty')
            <p class="rounded-xl border border-warning-500/40 bg-warning-500/10 p-3 text-sm text-ink">Unsaved changes — autosaving every 10 s…</p>
        @elseif ($autosaveState === 'saved')
            <p class="rounded-xl border border-success-500/40 bg-success-500/10 p-3 text-sm text-ink" role="status">All changes saved.</p>
        @endif
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="font-display text-lg">City blocks</h2>
            <div class="mt-3 flex flex-col gap-3">
                @forelse ($blocks as $i => $block)
                    <article class="rounded-xl border border-line bg-paper-2 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-ink">{{ ucfirst(str_replace('_',' ',$block['type'])) }}</p>
                            <button type="button" wire:click="removeBlock({{ $i }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-soft hover:bg-danger-500/10" aria-label="Remove block">✕</button>
                        </div>
                        <p class="mt-2 text-xs text-ink-muted">Full field editing follows the page-editor canvas; payload persists on save.</p>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-line p-6 text-center text-sm text-ink-soft">No blocks yet — add from the picker.</p>
                @endforelse
            </div>

            <div class="mt-4 rounded-xl border border-dashed border-line p-4">
                <p class="text-sm font-semibold text-ink">Add a block</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($definitions as $category => $types)
                        @foreach ($types as $type => $label)
                            <button type="button" wire:click="addBlock('{{ $type }}')"
                                    class="inline-flex min-h-[36px] items-center rounded-full border border-line bg-paper px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">+ {{ $label }}</button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <h2 class="font-display mt-6 text-lg">Coverage (services available here)</h2>
            <p class="mt-1 text-xs text-ink-muted">Coverage truth: a service shows on the city page only with a real row here.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($allServices as $service)
                    <label class="flex min-h-[44px] items-center gap-2 rounded-lg border border-line bg-paper-2 px-3 text-sm">
                        <input type="checkbox" wire:model="coverage.{{ $service->getKey() }}"
                               @checked(isset($coverage[$service->getKey()])) class="h-4 w-4 rounded border-line">
                        <span class="text-ink-soft">{{ $service->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <aside class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">City</h2>
                <div class="mt-3 grid gap-3">
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Name</span>
                        <input type="text" wire:model.live.debounce.300ms="name" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Slug</span>
                        <input type="text" wire:model.live.debounce.300ms="slug" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">State</span>
                        <input type="text" wire:model.live.debounce.300ms="state" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="flex min-h-[44px] items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="is_hub" class="h-4 w-4 rounded border-line"> <span class="font-semibold text-ink">Hub city</span>
                    </label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Intro (required to publish)</span>
                        <textarea wire:model.live.debounce.300ms="description" rows="4" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea></label>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">SEO</h2>
                <div class="mt-3 grid gap-3">
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Meta title</span>
                        <input type="text" wire:model.live.debounce.300ms="meta_title" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <span class="mt-1 block text-xs {{ str($meta_title)->length() > 60 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_title)->length() }}/60</span></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Meta description</span>
                        <textarea wire:model.live.debounce.300ms="meta_description" rows="3" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                        <span class="mt-1 block text-xs {{ str($meta_description)->length() > 160 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_description)->length() }}/160</span></label>
                    <label class="flex min-h-[44px] items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="noindex" class="h-4 w-4 rounded border-line"> <span class="font-semibold text-ink">noindex</span>
                    </label>
                    @if ($noindex)
                        <textarea wire:model="noindex_reason" rows="2" placeholder="Reason (required)" class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
