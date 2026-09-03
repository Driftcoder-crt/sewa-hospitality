{{-- B5 · Tabs (section-library §3) — deep-linkable via ?tab= (ui-
     components: reference's office tabs, upgraded). Alpine + history
     query sync; icon+label tabs; ARIA tablist semantics. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $uid = 'tabs-'.\Illuminate\Support\Str::random(5);
@endphp

<div {{ $attributes }}
     x-data="{ tab: new URLSearchParams(location.search).get('tab') ?? '0' }"
     @if (isset($data['anchor_id'])) id="{{ \Illuminate\Support\Str::slug($data['anchor_id']) }}" @endif>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto">
            <div class="flex flex-wrap gap-2" role="tablist">
                @foreach ($items as $i => $item)
                    <button type="button" role="tab"
                            :id="'{{ $uid }}-t{{ $i }}'"
                            :aria-selected="(tab === '{{ $i }}').toString()"
                            aria-controls="{{ $uid }}-p{{ $i }}"
                            @click="tab = '{{ $i }}'; const u = new URL(location); u.searchParams.set('tab', '{{ ($item['label'] ?? 'tab') ? \Illuminate\Support\Str::slug($item['label'] ?? 'tab') : $i }}'); history.replaceState(null, '', u)"
                            class="inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-semibold transition-colors"
                            :class="tab === '{{ $i }}' ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3'">
                        {{ $item['label'] ?? '' }}
                    </button>
                @endforeach
            </div>

            @foreach ($items as $i => $item)
                @php($clean = \App\Support\Cms\HtmlSanitizer::clean($item['content_html'] ?? ''))
                <div x-show="tab === '{{ $i }}'" role="tabpanel" id="{{ $uid }}-p{{ $i }}" :aria-labelledby="'{{ $uid }}-t{{ $i }}'"
                     class="mt-5 rounded-2xl border border-line bg-paper p-6 [&_p]:mt-2 [&_p]:leading-relaxed [&_p]:text-ink-soft [&_strong]:text-ink [&_a]:font-medium [&_a]:text-brand [&_a]:underline [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:ps-6 [&_li]:text-ink-soft">
                    {!! $clean !!}
                </div>
            @endforeach
        </div>
    </div>
</div>
