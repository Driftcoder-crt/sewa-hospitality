{{-- E3 · Offer Banner (section-library §6): dismissible promo strip
     with code chip + localStorage dismissal memory. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $theme = ($data['theme'] ?? 'brand') === 'sand' ? 'sand' : 'brand';
    $code = trim((string) ($data['code'] ?? ''));
    $memoryKey = 'sewa.offer.'.substr(md5((string) ($data['heading'] ?? '')), 0, 10);
@endphp

<section {{ $attributes }}>
    <div data-theme="{{ $theme }}" class="px-4 md:px-6" x-data="{ dismissed: localStorage.getItem(@js($memoryKey)) !== null }" x-show="! dismissed" x-cloak>
        <div class="container mx-auto flex flex-col items-start gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <p class="font-display text-lg text-paper">{{ $data['heading'] ?? '' }}</p>
                @if ($code !== '')
                    <code class="rounded-md border border-dashed border-paper/50 px-2 py-0.5 font-mono text-sm text-paper">{{ $code }}</code>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if (($data['cta_label'] ?? '') && ($data['cta_url'] ?? ''))
                    <a href="{{ $data['cta_url'] }}"
                       class="inline-flex min-h-[44px] items-center rounded-full bg-paper px-4 text-sm font-semibold text-brand">
                        {{ $data['cta_label'] }}
                    </a>
                @endif
                <button type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full text-paper/80 hover:bg-paper/10 hover:text-paper"
                        aria-label="Dismiss offer"
                        @click="localStorage.setItem(@js($memoryKey), '1'); dismissed = true">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>
