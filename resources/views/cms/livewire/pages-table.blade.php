<div class="admin-screen">
@section('title', 'Pages — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Pages</h1>
            <p class="eyebrow mt-1 text-ink-muted">Content · CMS</p>
        </div>
        <button wire:click="create" type="button"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New page
        </button>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search title or slug…"
               class="min-h-[44px] w-full max-w-xs rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand sm:w-64">
        <select wire:model.live="status" aria-label="Filter by status"
                class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="type" aria-label="Filter by type"
                class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-start text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">Title</th>
                    <th class="px-4 py-3 text-start font-semibold">Slug / path</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                    <th class="px-4 py-3 text-start font-semibold">Updated</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3 font-medium text-ink">{{ $page->title }}</td>
                        <td class="px-4 py-3">
                            <code class="text-xs text-ink-soft">{{ $page->publicPath() }}</code>
                            <span class="mt-0.5 block text-xs text-ink-muted">{{ ucfirst($page->type->value) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $tones = ['published' => 'bg-success-500/15 text-ink', 'draft' => 'bg-paper-3 text-ink-soft', 'scheduled' => 'bg-warning-500/15 text-ink', 'archived' => 'bg-paper-3 text-ink-muted'];
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tones[$page->status->value] ?? $tones['draft'] }}">
                                {{ $page->status->label() }}
                            </span>
                            @if ($page->noindex)
                                <span class="ms-1 inline-flex items-center rounded-full bg-danger-500/15 px-2 py-1 text-xs font-semibold text-ink" title="noindex — requires confirmation to publish">noindex</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-soft">{{ $page->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @if ($page->status->isPublic())
                                    <a href="{{ $page->publicPath() }}" target="_blank" rel="noopener"
                                       class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">View</a>
                                @endif
                                @if (! $page->status->isPublic())
                                    <button wire:click="publish('{{ $page->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Publish</button>
                                @else
                                    <button wire:click="unpublish('{{ $page->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Unpublish</button>
                                @endif
                                <button wire:click="duplicate('{{ $page->getKey() }}')" type="button"
                                        class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Duplicate</button>
                                <a href="{{ route('admin.pages.edit', ['page' => $page->getKey()]) }}"
                                   class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10">
                            <div class="flex flex-col items-center gap-2 text-center">
                                <p class="font-display text-lg text-ink">No pages match</p>
                                <p class="text-sm text-ink-soft">Adjust the filters, or create the page you need.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>
</div>
