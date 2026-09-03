{{-- ⌘K command palette (admin-panel §2): dialog with focus management,
     keyboard navigation (↑/↓/Enter/Esc), role-scoped results. --}}
<div x-data="{ open: false, highlight: 0 }"
     x-init="
        window.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open = true; $wire.openPalette(); }
        });
        $watch('open', v => { if (v) { $refs.input.focus(); } });
     "
     x-on:open-palette.window="open = true; $wire.openPalette()"
     @keydown.escape.window="open = false"
     @keydown.arrow-down.window="if (open) { highlight = Math.min(highlight + 1, $wire.results.length - 1); }"
     @keydown.arrow-up.window="if (open) { highlight = Math.max(highlight - 1, 0); }"
     @keydown.enter.window="if (open && $wire.results[highlight]) { window.location = $wire.results[highlight].url; }">
    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Command palette">
        <div class="absolute inset-0 bg-ink-900/50" @click="open = false" aria-hidden="true"></div>

        <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-line bg-paper shadow-2xl" x-trap.inert.noscroll="open">
            <div class="border-b border-line p-3">
                <input type="text"
                       x-ref="input"
                       wire:model.live.debounce.200ms="query"
                       placeholder="Search pages, screens…"
                       class="w-full rounded-lg border border-line bg-paper-2 px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                       aria-label="Search commands and pages"
                       @input="highlight = 0">
            </div>

            <ul class="max-h-80 overflow-y-auto p-2" role="listbox" aria-label="Results">
                @forelse ($results as $index => $result)
                    <li role="option" aria-selected="{{ $index === $highlight ? 'true' : 'false' }}">
                        <a href="{{ $result['url'] }}"
                           @mouseenter="highlight = {{ $index }}"
                           @if ($index === $highlight) aria-current="true" @endif
                           class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 {{ $index === $highlight ? 'bg-paper-3' : '' }}">
                            <span class="flex min-w-0 flex-col">
                                <span class="truncate text-sm font-medium text-ink">{{ $result['label'] }}</span>
                                <span class="truncate text-xs text-ink-muted">{{ $result['hint'] }}</span>
                            </span>
                            <span class="eyebrow shrink-0 text-ink-muted">{{ $result['group'] }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-3 py-6 text-center text-sm text-ink-muted">No matches — try a page title or screen name.</li>
                @endforelse
            </ul>

            <div class="flex items-center justify-between border-t border-line px-4 py-2 text-xs text-ink-muted">
                <span>↑↓ navigate · ↵ open · esc close</span>
                <span>Role-scoped results</span>
            </div>
        </div>
    </div>
</div>
