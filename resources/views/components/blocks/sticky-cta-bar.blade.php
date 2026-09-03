{{-- E8 · Sticky CTA Bar (section-library §6): mobile bottom bar +
     desktop rail, dismissible with memory, safe-area aware (expat
     mobile behavior: phone-first contact). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $memoryKey = 'sewa.ctabar.'.substr(md5(json_encode($items)), 0, 10);
@endphp

<section {{ $attributes }} class="contents"
    x-data="{ hidden: localStorage.getItem(@js($memoryKey)) !== null }">
    <div x-cloak x-show="! hidden"
         class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-paper-2/95 backdrop-blur supports-[backdrop-filter]:bg-paper-2/80 md:inset-x-auto md:bottom-6 md:end-6 md:rounded-2xl md:border md:shadow-lg"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex items-stretch justify-between gap-1 px-2 py-1.5 md:flex-col md:gap-2 md:p-3">
            @if (($data['heading'] ?? '') !== '')
                <p class="hidden font-display text-sm md:block">{{ $data['heading'] }}</p>
            @endif

            @foreach ($items as $item)
                @php($icon = match ($item['icon'] ?? '') { 'call' => 'call', 'whatsapp' => 'chat', 'chat' => 'chat', default => 'chat' })
                <a href="{{ $item['url'] ?? '#' }}"
                   @class([
                       'flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold',
                       'bg-brand text-brand-ink' => ($item['icon'] ?? '') === 'call',
                       'text-ink hover:bg-paper-3' => ($item['icon'] ?? '') !== 'call',
                   ])>
                    @if ($icon === 'call')
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5Z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 0 1-2.893-.456l-3.392 1.13a.75.75 0 0 1-.95-.95l1.13-3.392A8.315 8.315 0 0 1 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7Zm-8.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12 10a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm2.5.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                    @endif
                    {{ $item['label'] ?? '' }}
                </a>
            @endforeach

            <button type="button"
                    class="hidden min-h-[44px] w-11 items-center justify-center rounded-xl text-ink-muted hover:bg-paper-3 md:flex"
                    aria-label="Hide the quick actions bar"
                    @click="localStorage.setItem(@js($memoryKey), '1'); hidden = true">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>

        {{-- Mobile dismiss — tiny, below the actions, never blocks tapping them --}}
        <button type="button"
                class="mx-auto mb-1 block text-[11px] text-ink-muted md:hidden"
                @click="localStorage.setItem(@js($memoryKey), '1'); hidden = true">
            Hide
        </button>
    </div>
</section>
